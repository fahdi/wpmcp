<?php

namespace WPMCP\Tools\Elementor;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Shape an Elementor Widget_Base instance into the safe summary row and the
 * control-schema row shared by list-widgets and get-widget-schema.
 */
class Widget_View
{
    /**
     * @return array{name: string, title: string, categories: array<int, string>, icon: string, tier: string}
     */
    public static function summary(\Elementor\Widget_Base $widget): array
    {
        return [
            'name'       => $widget->get_name(),
            'title'      => $widget->get_title(),
            'categories' => $widget->get_categories(),
            'icon'       => $widget->get_icon(),
            'tier'       => self::tier($widget),
        ];
    }

    /**
     * Elementor Pro widgets are namespaced under ElementorPro\...; every
     * bundled Elementor (free) widget lives under the Elementor\ namespace.
     * This is the same signal Elementor Pro itself relies on to distinguish
     * its own widgets, so it holds regardless of how a given widget is wired.
     */
    public static function tier(\Elementor\Widget_Base $widget): string
    {
        return 0 === strpos(get_class($widget), 'ElementorPro\\') ? 'pro' : 'free';
    }
}
