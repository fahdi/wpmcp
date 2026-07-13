<?php

namespace WPMCP\Tools\Builders;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read/write access to a page's Bricks builder structure, stored as JSON in
 * the `_bricks_page_content_2` postmeta key. Read and written directly as
 * postmeta rather than through the Bricks plugin's own runtime, matching
 * how Elementor_Page_Data treats `_elementor_data`: because it is ordinary
 * postmeta on the page post, the existing post snapshot in
 * Safe_Mutation::run() already captures and restores it, so no safety-core
 * change is needed for these edits to be undoable.
 */
class Bricks_Content
{
    public const META_KEY = '_bricks_page_content_2';

    /** Decode the stored JSON into an array, or null if missing/invalid. */
    public static function get(int $post_id): ?array
    {
        $raw = get_post_meta($post_id, self::META_KEY, true);

        if (empty($raw) || ! is_string($raw)) {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    public static function save(int $post_id, array $elements): void
    {
        update_post_meta($post_id, self::META_KEY, wp_json_encode($elements));
    }
}
