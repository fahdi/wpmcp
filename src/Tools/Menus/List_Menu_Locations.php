<?php

namespace WPMCP\Tools\Menus;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: list the theme's registered navigation menu locations and which
 * menu (if any) is currently assigned to each.
 *
 * Registered locations come from get_registered_nav_menus(); the current
 * assignments live in the nav_menu_locations theme_mod (an option). We join
 * the two so a caller can see, per location, its slug, human description, and
 * the assigned menu id (0 when unassigned). Reads have nothing to roll back,
 * so this never touches Safe_Mutation.
 */
class List_Menu_Locations
{
    public function handle(array $args): array
    {
        $registered = get_registered_nav_menus();
        $assigned   = (array) get_theme_mod('nav_menu_locations', []);
        $rows       = [];

        foreach ($registered as $location => $description) {
            $rows[] = [
                'location'    => $location,
                'description' => $description,
                'menu_id'     => (int) ($assigned[ $location ] ?? 0),
            ];
        }

        return ['locations' => $rows];
    }
}
