<?php

namespace WPMCP\Tools\Elementor;

use WPMCP\Safety\Safe_Mutation;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Add a widget element (given a widget_type and optional settings) as a
 * child of a specified parent element in a page's `_elementor_data`. The
 * widget_type is validated against Elementor's own widgets_manager before
 * anything is written, so an unknown type never reaches the page data.
 * Reads `_elementor_data`, mutates the tree, and writes it back through
 * Safe_Mutation::run() with object_type='post': `_elementor_data` is
 * ordinary postmeta on the page, so the existing post snapshot captures and
 * restores it, making this edit undoable with no change to the safety core.
 */
class Add_Widget
{
    public function handle(array $args)
    {
        $post_id     = (int) ($args['post_id'] ?? 0);
        $parent_id   = (string) ($args['parent_id'] ?? '');
        $widget_type = (string) ($args['widget_type'] ?? '');
        $settings    = is_array($args['settings'] ?? null) ? $args['settings'] : [];

        if ($post_id <= 0) {
            return new \WP_Error('missing_post_id', 'A post_id is required.');
        }

        if ('' === $parent_id) {
            return new \WP_Error('missing_parent_id', 'A parent_id is required.');
        }

        if ('' === $widget_type) {
            return new \WP_Error('missing_widget_type', 'A widget_type is required.');
        }

        if (! class_exists('\\Elementor\\Plugin')) {
            return new \WP_Error('elementor_not_active', 'Elementor is not active on this site.');
        }

        $widget = \Elementor\Plugin::instance()->widgets_manager->get_widget_types($widget_type);

        if (null === $widget) {
            return new \WP_Error('invalid_widget_type', "Unknown Elementor widget type '{$widget_type}'.");
        }

        $elements = Elementor_Page_Data::get($post_id);

        if (null === Elementor_Page_Data::find($elements, $parent_id)) {
            return new \WP_Error('parent_not_found', "No element found with id '{$parent_id}'.");
        }

        $element_id = Element_Id::generate();

        $out = Safe_Mutation::run(
            [
                'object_type' => 'post',
                'object_id'   => $post_id,
                'session_id'  => (string) ($args['session_id'] ?? 'default'),
                'tool_name'   => 'add-widget',
                'args'        => $args,
            ],
            function () use ($post_id, $parent_id, $widget_type, $settings, $element_id) {
                $elements = Elementor_Page_Data::get($post_id);
                Elementor_Page_Data::insert($elements, $parent_id, [
                    'id'         => $element_id,
                    'elType'     => 'widget',
                    'widgetType' => $widget_type,
                    'settings'   => $settings,
                    'elements'   => [],
                ]);
                Elementor_Page_Data::save($post_id, $elements);
                return true;
            }
        );

        return ['operation_id' => $out['operation_id'], 'post_id' => $post_id, 'element_id' => $element_id];
    }
}
