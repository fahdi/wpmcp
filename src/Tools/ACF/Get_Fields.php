<?php

namespace WPMCP\Tools\ACF;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: return a post's ACF field values, keyed by field name, via
 * get_fields(). Reads have nothing to roll back, so this never touches
 * Safe_Mutation.
 *
 * get_fields() returns null when a post has no ACF data at all (rather than
 * an empty array), so that case is normalized to an empty fields map here.
 */
class Get_Fields
{
    public function handle(array $args): array
    {
        $post_id = (int) ($args['post_id'] ?? 0);
        if ($post_id <= 0) {
            throw new \InvalidArgumentException('A post id is required.');
        }

        $fields = get_fields($post_id);

        return ['post_id' => $post_id, 'fields' => is_array($fields) ? $fields : []];
    }
}
