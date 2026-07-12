<?php

namespace WPMCP\Tools\Elementor;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: list Elementor registered widget types (name, title, categories,
 * icon, tier) via Elementor's own widgets manager. Supports optional filtering
 * by tier (free/pro) and a case-insensitive search over name/title.
 *
 * Never mutates anything, so this is not routed through the safety core.
 * Degrades gracefully with a WP_Error when Elementor is not loaded.
 */
class List_Widgets
{
    public function handle(array $args)
    {
        if (! class_exists('\\Elementor\\Plugin')) {
            return new \WP_Error('elementor_not_active', 'Elementor is not active on this site.');
        }

        $widget_types = \Elementor\Plugin::instance()->widgets_manager->get_widget_types();

        $rows = [];
        foreach ($widget_types as $widget) {
            $row = Widget_View::summary($widget);

            if (! empty($args['tier']) && $row['tier'] !== $args['tier']) {
                continue;
            }

            if (! empty($args['category']) && ! in_array($args['category'], $row['categories'], true)) {
                continue;
            }

            if (! empty($args['search']) && ! self::matches_search($row, (string) $args['search'])) {
                continue;
            }

            $rows[] = $row;
        }

        return [ 'widgets' => $rows ];
    }

    private static function matches_search(array $row, string $search): bool
    {
        $needle = strtolower($search);

        return false !== strpos(strtolower($row['name']), $needle)
            || false !== strpos(strtolower($row['title']), $needle);
    }
}
