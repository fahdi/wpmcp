<?php

namespace WPMCP\Tools\SEO;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: return a post's SEO title, meta description, focus keyword,
 * canonical URL, and robots flags (noindex/nofollow) via the active SEO
 * plugin's postmeta keys, through SEO_Adapter. Reads have nothing to roll
 * back, so this never touches Safe_Mutation.
 */
class Get_SEO_Meta
{
    public function handle(array $args): array
    {
        $post_id = (int) ($args['post_id'] ?? 0);
        if ($post_id <= 0) {
            throw new \InvalidArgumentException('A post id is required.');
        }

        return array_merge(['post_id' => $post_id], SEO_Adapter::get_meta($post_id));
    }
}
