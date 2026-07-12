<?php

namespace WPMCP\Tools\Performance;

if (! defined('ABSPATH')) {
    exit;
}

class Analyzer
{
    private const CRITICAL_WEIGHT     = 15;
    private const WARNING_WEIGHT      = 4;
    private const TOP_RECOMMENDATIONS = 8;

    private Server_Audit $server;
    private Page_Audit $page;

    public function __construct(?Server_Audit $server = null, ?Page_Audit $page = null)
    {
        $this->server = $server ?: new Server_Audit();
        $this->page   = $page ?: new Page_Audit();
    }

    /**
     * @param array $input { url?, post_id?, include_page_fetch?, deep_assets? }
     * @return array|\WP_Error
     */
    public function analyze(array $input)
    {
        $target = $this->resolve_target($input);
        if (is_wp_error($target)) {
            return $target;
        }

        $findings   = $this->server->run();
        $page_fetch = ['ok' => false, 'status_code' => 0, 'response_ms' => 0, 'total_bytes' => 0, 'error' => 'not_requested'];

        $do_fetch = ! isset($input['include_page_fetch']) || (bool) $input['include_page_fetch'];
        if ($do_fetch) {
            $fetched    = $this->page->fetch($target['resolved_url']);
            $result     = $this->page->analyze($fetched, ! empty($input['deep_assets']));
            $findings   = array_merge($findings, $result['findings']);
            $page_fetch = $result['page_fetch'];
        }

        $summary  = $this->summarize($findings);
        $sections = $this->group_by_category($findings);

        return [
            'target'              => $target,
            'summary'             => [
                'score'  => $summary['score'],
                'grade'  => $summary['grade'],
                'counts' => $summary['counts'],
            ],
            'sections'            => $sections,
            'page_fetch'          => $page_fetch,
            'top_recommendations' => $summary['top_recommendations'],
        ];
    }

    /**
     * Resolve url|post_id|frontpage to a same-host absolute URL.
     *
     * @return array|\WP_Error { resolved_url, post_id, is_front_page }
     */
    private function resolve_target(array $input)
    {
        $site_host = (string) wp_parse_url(home_url(), PHP_URL_HOST);

        if (! empty($input['url'])) {
            $url = esc_url_raw((string) $input['url']);
            if ('' === $url || ! $this->validate_same_host($url, $site_host)) {
                return new \WP_Error('invalid_target', 'The url must be a page on this site.');
            }
            return ['resolved_url' => $url, 'post_id' => null, 'is_front_page' => false];
        }

        if (! empty($input['post_id'])) {
            $post_id = (int) $input['post_id'];
            $link    = get_permalink($post_id);
            if (! $link) {
                return new \WP_Error('invalid_target', 'No published page found for that post_id.');
            }
            return ['resolved_url' => $link, 'post_id' => $post_id, 'is_front_page' => false];
        }

        return ['resolved_url' => home_url('/'), 'post_id' => null, 'is_front_page' => true];
    }

    /**
     * @param array $findings Finding[]
     * @return array category => Finding[]
     */
    private function group_by_category(array $findings): array
    {
        $sections = ['server' => [], 'database' => [], 'config' => [], 'page' => [], 'assets' => []];
        foreach ($findings as $finding) {
            $category = (string) ($finding['category'] ?? 'server');
            if (! isset($sections[ $category ])) {
                $sections[ $category ] = [];
            }
            $sections[ $category ][] = $finding;
        }
        return $sections;
    }

    /**
     * Pure: counts, score, grade for a set of findings.
     *
     * @param array $findings Finding[]
     */
    public function summarize(array $findings): array
    {
        $counts = ['critical' => 0, 'warning' => 0, 'pass' => 0, 'info' => 0];
        foreach ($findings as $finding) {
            $status = (string) ($finding['status'] ?? 'info');
            if (isset($counts[ $status ])) {
                $counts[ $status ]++;
            }
        }

        $score = 100 - ($counts['critical'] * self::CRITICAL_WEIGHT) - ($counts['warning'] * self::WARNING_WEIGHT);
        $score = max(0, min(100, $score));

        if ($score >= 90) {
            $grade = 'A';
        } elseif ($score >= 80) {
            $grade = 'B';
        } elseif ($score >= 70) {
            $grade = 'C';
        } elseif ($score >= 60) {
            $grade = 'D';
        } else {
            $grade = 'F';
        }

        return [
            'counts'              => $counts,
            'score'               => $score,
            'grade'               => $grade,
            'top_recommendations' => $this->rank_recommendations($findings),
        ];
    }

    /**
     * Pure: is $url on $site_host?
     */
    public function validate_same_host(string $url, string $site_host): bool
    {
        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        return '' !== $host && strtolower($host) === strtolower($site_host);
    }

    /**
     * @param array $findings Finding[]
     * @return string[]
     */
    private function rank_recommendations(array $findings): array
    {
        $critical = [];
        $warning  = [];

        foreach ($findings as $finding) {
            $recommendation = trim((string) ($finding['recommendation'] ?? ''));
            if ('' === $recommendation) {
                continue;
            }
            $line = sprintf('[%s] %s', (string) ($finding['label'] ?? ''), $recommendation);
            if ('critical' === ($finding['status'] ?? '')) {
                $critical[] = $line;
            } elseif ('warning' === ($finding['status'] ?? '')) {
                $warning[] = $line;
            }
        }

        return array_slice(array_merge($critical, $warning), 0, self::TOP_RECOMMENDATIONS);
    }
}
