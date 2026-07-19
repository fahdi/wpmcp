<?php

namespace WPMCP\Tools\Elementor;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: return a page's parsed `_elementor_data` element tree (id,
 * elType, widgetType, settings, and nested elements for every node), read
 * straight from postmeta. Never mutates anything, so this is not routed
 * through the safety core.
 *
 * Also the read half of the structural suite's concurrency contract
 * (issue #58): data_hash (sha256 of the raw `_elementor_data` JSON) and
 * settings_hash (sha256 of the JSON-encoded `_elementor_page_settings`)
 * are what the structural mutations require back as expected_hash, so a
 * write can prove the page did not change between read and write.
 */
class Get_Elementor_Data
{
    public function handle(array $args)
    {
        $post_id = (int) ($args['post_id'] ?? 0);

        if ($post_id <= 0) {
            return new \WP_Error('missing_post_id', 'A post_id is required.');
        }

        return [
            'post_id'       => $post_id,
            'elements'      => Elementor_Page_Data::get($post_id),
            'data_hash'     => Element_Tree::data_hash($post_id),
            'page_settings' => Element_Tree::page_settings($post_id),
            'settings_hash' => Element_Tree::settings_hash($post_id),
        ];
    }
}
