<?php

namespace WPMCP\Tools\Elementor;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: return the full control schema for a single Elementor widget
 * type (control name, type, label, default, and the section it belongs to),
 * read straight from the widget's own get_controls(). This is the full
 * control set as Elementor defines it, not a hand-curated subset, since
 * Elementor is itself the source of truth for what a widget accepts.
 *
 * Never mutates anything, so this is not routed through the safety core.
 * Degrades gracefully with a WP_Error when Elementor is not loaded.
 */
class Get_Widget_Schema
{
    public function handle(array $args)
    {
        if (! class_exists('\\Elementor\\Plugin')) {
            return new \WP_Error('elementor_not_active', 'Elementor is not active on this site.');
        }

        if (empty($args['widget_name'])) {
            return new \WP_Error('missing_widget_name', 'A widget_name is required.');
        }

        $widget_name = (string) $args['widget_name'];
        $widget      = \Elementor\Plugin::instance()->widgets_manager->get_widget_types($widget_name);

        if (null === $widget) {
            return new \WP_Error('unknown_widget', "No Elementor widget registered as '{$widget_name}'.");
        }

        return [
            'widget_name' => $widget_name,
            'controls'    => Widget_View::controls($widget),
        ];
    }
}
