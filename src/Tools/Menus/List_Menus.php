<?php

namespace WPMCP\Tools\Menus;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: list the site's navigation menus as safe summary rows.
 *
 * A WordPress nav menu is a term in the nav_menu taxonomy. wp_get_nav_menus()
 * returns those terms; we reduce each to a small, safe shape (id, name, slug,
 * item count) rather than leaking full term objects. Reads have nothing to
 * roll back, so this never touches Safe_Mutation.
 */
class List_Menus
{
    public function handle(array $args): array
    {
        $menus = wp_get_nav_menus();
        $rows  = [];

        foreach ($menus as $menu) {
            $rows[] = [
                'id'    => (int) $menu->term_id,
                'name'  => $menu->name,
                'slug'  => $menu->slug,
                'count' => (int) $menu->count,
            ];
        }

        return ['menus' => $rows];
    }
}
