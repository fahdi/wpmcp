<?php

namespace WPMCP\Tools\Linking;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Builds the internal-link graph for a bounded set of published posts.
 *
 * For each of the most-recent N published posts of the requested post types
 * it scans the stored content for <a href> values and resolves each href to
 * a local post ID (permalink, ?p=<id>, or slug). The result is a per-post map
 * of outgoing edges plus an incoming count, shared by all three Linking tools
 * so the resolution logic lives in exactly one place.
 *
 * Read-only: nothing here writes, so it never touches the safety core.
 */
class Link_Graph
{
    /** Hard ceiling on posts scanned, so a huge site cannot stall a build. */
    public const MAX_SCAN = 500;

    /**
     * @param string[] $post_types Post types to include in the graph.
     * @param int      $limit      Most-recent-N cap on posts scanned.
     * @return array<int, array{title: string, post_type: string, outgoing: int[], incoming: int}>
     */
    public static function build(array $post_types, int $limit): array
    {
        $limit = max(1, min($limit, self::MAX_SCAN));

        $query = new \WP_Query([
            'post_type'      => array_values($post_types),
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
            'fields'         => 'all',
        ]);

        $posts = $query->posts;
        $graph = [];
        $known = [];
        foreach ($posts as $post) {
            $graph[$post->ID] = [
                'title'     => (string) $post->post_title,
                'post_type' => (string) $post->post_type,
                'outgoing'  => [],
                'incoming'  => 0,
            ];
            $known[$post->ID] = true;
        }

        foreach ($posts as $post) {
            $targets = self::resolve_targets((string) $post->post_content, $post->ID);
            foreach ($targets as $target_id) {
                if (! isset($known[$target_id])) {
                    continue;
                }
                if (! in_array($target_id, $graph[$post->ID]['outgoing'], true)) {
                    $graph[$post->ID]['outgoing'][] = $target_id;
                    $graph[$target_id]['incoming']++;
                }
            }
        }

        return $graph;
    }

    /**
     * Extract <a href> values from content and resolve each to a local post ID.
     *
     * @return int[] Unique local post IDs the content links to (self excluded).
     */
    private static function resolve_targets(string $content, int $self_id): array
    {
        if ('' === $content || false === strpos($content, 'href')) {
            return [];
        }

        if (! preg_match_all('/<a\b[^>]*\bhref\s*=\s*["\']([^"\']+)["\']/i', $content, $matches)) {
            return [];
        }

        $ids = [];
        foreach ($matches[1] as $href) {
            $id = self::resolve_href((string) $href);
            if ($id > 0 && $id !== $self_id) {
                $ids[$id] = true;
            }
        }

        return array_keys($ids);
    }

    /** Resolve a single href to a local post ID, or 0 if it is not an internal post link. */
    private static function resolve_href(string $href): int
    {
        $href = trim($href);
        if ('' === $href || '#' === $href[0]) {
            return 0;
        }

        $home_host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
        $href_host = strtolower((string) wp_parse_url($href, PHP_URL_HOST));
        if ('' !== $href_host && $href_host !== $home_host) {
            return 0;
        }

        $id = (int) url_to_postid($href);
        if ($id > 0) {
            return $id;
        }

        $query = (string) wp_parse_url($href, PHP_URL_QUERY);
        if ('' !== $query) {
            parse_str($query, $args);
            if (isset($args['p']) && is_numeric($args['p'])) {
                return (int) $args['p'];
            }
        }

        return 0;
    }
}
