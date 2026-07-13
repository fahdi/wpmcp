<?php

namespace WPMCP\Tools\Linking;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: return published posts that have zero incoming internal links.
 *
 * Builds the internal-link graph via Link_Graph, then reports every scanned
 * post no other scanned post links to. Supports a post-type filter and caps
 * both the scan (via Link_Graph) and the returned list. Reads have nothing to
 * roll back, so this never touches Safe_Mutation.
 */
class Find_Orphan_Posts
{
    private const DEFAULT_SCAN = 200;
    private const DEFAULT_CAP  = 100;

    public function handle(array $args): array
    {
        $post_types = Args::post_types($args['post_type'] ?? 'post');
        $scan       = Args::scan_limit($args['limit'] ?? self::DEFAULT_SCAN, self::DEFAULT_SCAN);
        $cap        = Args::cap($args['cap'] ?? self::DEFAULT_CAP, self::DEFAULT_CAP);

        $graph = Link_Graph::build($post_types, $scan);

        $orphans = [];
        foreach ($graph as $id => $node) {
            if (0 === $node['incoming']) {
                $orphans[] = [
                    'id'        => $id,
                    'title'     => $node['title'],
                    'post_type' => $node['post_type'],
                ];
            }
        }

        return [
            'orphan_total' => count($orphans),
            'scanned'      => count($graph),
            'orphans'      => array_slice($orphans, 0, $cap),
        ];
    }
}
