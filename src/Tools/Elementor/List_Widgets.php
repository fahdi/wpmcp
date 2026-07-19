<?php

namespace WPMCP\Tools\Elementor;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: list Elementor registered widget types (name, title,
 * categories, icon, tier, availability) via Elementor's own widgets manager,
 * annotated from the curated catalog (issue #59): cataloged widgets carry
 * their one-line purpose, and searches match curated keywords as well as
 * name/title. Elementor Pro promotion placeholders are reported as
 * unavailable pro widgets, never as free ones.
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
        $catalog      = Widget_Catalog::summaries();

        $rows = [];
        foreach ($widget_types as $widget) {
            $row   = Widget_View::summary($widget);
            $entry = $catalog[ $row['name'] ] ?? null;

            $row['cataloged'] = null !== $entry;
            $row['purpose']   = $entry['purpose'] ?? null;

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

        if (
            false !== strpos(strtolower($row['name']), $needle)
            || false !== strpos(strtolower($row['title']), $needle)
        ) {
            return true;
        }

        $entry = Widget_Catalog::get($row['name']);
        foreach ($entry['keywords'] ?? [] as $keyword) {
            if (false !== strpos(strtolower($keyword), $needle)) {
                return true;
            }
        }

        return false;
    }
}
