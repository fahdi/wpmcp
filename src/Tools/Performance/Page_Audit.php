<?php

namespace WPMCP\Tools\Performance;

if (! defined('ABSPATH')) {
    exit;
}

class Page_Audit
{
    private const RESPONSE_WARN_MS  = 800;
    private const HTML_WARN_BYTES   = 512000; // 500 KB of HTML.
    private const RENDER_BLOCK_WARN = 5;

    /**
     * Parse a fetched struct into findings plus a page_fetch meta block. Pure.
     *
     * @param array $fetched     Output of fetch().
     * @param bool  $deep_assets Reserved for bounded asset-size sampling.
     * @return array { findings: Finding[], page_fetch: array }
     */
    public function analyze(array $fetched, bool $deep_assets): array
    {
        $page_fetch = [
            'ok'          => ! empty($fetched['ok']),
            'status_code' => (int) ($fetched['status_code'] ?? 0),
            'response_ms' => (int) ($fetched['response_ms'] ?? 0),
            'total_bytes' => (int) ($fetched['total_bytes'] ?? 0),
            'error'       => $fetched['error'] ?? null,
        ];

        if (empty($fetched['ok'])) {
            $findings = [
                Finding::make(
                    'page_fetch',
                    'page',
                    'Page fetch',
                    'warning',
                    false,
                    sprintf('Could not fetch the page: %s', (string) ($fetched['error'] ?? 'unknown error')),
                    'The request failed (often a local firewall, DNS, or self-signed SSL issue). Server and database checks are still reported.'
                ),
            ];
            return ['findings' => $findings, 'page_fetch' => $page_fetch];
        }

        $status   = (int) ($fetched['status_code'] ?? 0);
        $findings = [];

        $findings[] = (200 === $status)
            ? Finding::make('http_status', 'page', 'HTTP status', 'pass', $status, 'Page returned HTTP 200.')
            : Finding::make(
                'http_status',
                'page',
                'HTTP status',
                'warning',
                $status,
                sprintf('Page returned HTTP %d.', $status),
                'A non-200 status means the analyzed URL is redirecting or erroring; verify the target.'
            );

        $ms         = (int) ($fetched['response_ms'] ?? 0);
        $findings[] = ($ms > self::RESPONSE_WARN_MS)
            ? Finding::make(
                'response_time',
                'page',
                'Server response time',
                'warning',
                $ms,
                sprintf('Full HTML response took %d ms.', $ms),
                'Add page caching (a cache plugin or server cache) so HTML is served without a full PHP/DB render.'
            )
            : Finding::make('response_time', 'page', 'Server response time', 'pass', $ms, sprintf('Full HTML response took %d ms.', $ms));

        $bytes      = (int) ($fetched['total_bytes'] ?? 0);
        $findings[] = ($bytes > self::HTML_WARN_BYTES)
            ? Finding::make(
                'html_size',
                'page',
                'HTML size',
                'warning',
                $bytes,
                sprintf('The HTML document is %d KB.', (int) round($bytes / 1024)),
                'Large HTML often means inlined data or huge page builders; trim markup and avoid inlining big payloads.'
            )
            : Finding::make('html_size', 'page', 'HTML size', 'pass', $bytes, sprintf('The HTML document is %d KB.', (int) round($bytes / 1024)));

        $headers  = (array) ($fetched['headers'] ?? []);
        $encoding = strtolower((string) ($headers['content-encoding'] ?? ''));
        $findings[] = (false !== strpos($encoding, 'gzip') || false !== strpos($encoding, 'br'))
            ? Finding::make('compression', 'page', 'Compression', 'pass', $encoding, sprintf('Response is compressed (%s).', $encoding))
            : Finding::make(
                'compression',
                'page',
                'Compression',
                'warning',
                $encoding ?: 'none',
                'Response is not gzip/brotli compressed.',
                'Enable gzip or brotli at the server (or via a cache plugin) to cut transfer size.'
            );

        $has_cache_headers = ! empty($headers['cache-control']) || ! empty($headers['expires']) || ! empty($headers['x-cache']);
        $findings[]        = $has_cache_headers
            ? Finding::make('cache_headers', 'page', 'Cache headers', 'pass', true, 'The page sends caching headers.')
            : Finding::make(
                'cache_headers',
                'page',
                'Cache headers',
                'warning',
                false,
                'No Cache-Control / Expires headers on the HTML.',
                'A page cache that emits Cache-Control lets browsers and CDNs reuse the response.'
            );

        $body = (string) ($fetched['body'] ?? '');
        $host = (string) ($fetched['host'] ?? '');
        $dom  = $this->parse_dom($body);
        if (null !== $dom) {
            $findings = array_merge($findings, $this->asset_findings($dom, $host));
        }

        return ['findings' => $findings, 'page_fetch' => $page_fetch];
    }

    private function parse_dom(string $html): ?\DOMDocument
    {
        if ('' === trim($html)) {
            return null;
        }
        $previous = libxml_use_internal_errors(true);
        $dom      = new \DOMDocument();
        $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return $dom;
    }

    private function asset_findings(\DOMDocument $dom, string $host): array
    {
        $links   = $dom->getElementsByTagName('link');
        $scripts = $dom->getElementsByTagName('script');

        $head_css = 0;
        foreach ($links as $link) {
            $rel = strtolower((string) $link->getAttribute('rel'));
            if ('stylesheet' !== $rel) {
                continue;
            }
            if ($this->in_head($link)) {
                $head_css++;
            }
        }

        $sync_head = 0;
        foreach ($scripts as $script) {
            $src = (string) $script->getAttribute('src');
            if ('' === $src) {
                continue; // inline.
            }
            $is_async = $script->hasAttribute('async') || $script->hasAttribute('defer');
            if (! $is_async && $this->in_head($script)) {
                $sync_head++;
            }
        }

        $render_blocking = $head_css + $sync_head;

        $findings   = [];
        $findings[] = ($render_blocking > self::RENDER_BLOCK_WARN)
            ? Finding::make(
                'render_blocking',
                'assets',
                'Render-blocking resources',
                'warning',
                $render_blocking,
                sprintf('%d render-blocking resources in <head> (%d CSS, %d sync JS).', $render_blocking, $head_css, $sync_head),
                'Defer non-critical JS (async/defer) and combine or inline critical CSS to unblock first paint.'
            )
            : Finding::make(
                'render_blocking',
                'assets',
                'Render-blocking resources',
                'info',
                $render_blocking,
                sprintf('%d render-blocking resources in <head> (%d CSS, %d sync JS).', $render_blocking, $head_css, $sync_head)
            );

        return $findings;
    }

    private function in_head(\DOMNode $node): bool
    {
        for ($parent = $node->parentNode; null !== $parent; $parent = $parent->parentNode) {
            if ('head' === strtolower($parent->nodeName)) {
                return true;
            }
        }
        return false;
    }
}
