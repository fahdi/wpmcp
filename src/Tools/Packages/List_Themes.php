<?php

namespace WPMCP\Tools\Packages;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only listing of installed themes. Direct read, no snapshot: nothing
 * is mutated here.
 */
class List_Themes
{
    public function handle(array $args): array
    {
        $active_stylesheet = get_stylesheet();
        $update_themes      = get_site_transient('update_themes');
        $updates            = is_object($update_themes) ? (array) ($update_themes->response ?? []) : [];

        $rows = [];
        foreach (wp_get_themes() as $stylesheet => $theme) {
            $has_update = isset($updates[ $stylesheet ]);
            $new_version = null;
            if ($has_update) {
                $entry       = $updates[ $stylesheet ];
                $new_version = is_array($entry) ? ($entry['new_version'] ?? null) : ($entry->new_version ?? null);
            }

            $rows[] = [
                'stylesheet'       => $stylesheet,
                'name'             => $theme->get('Name'),
                'version'          => $theme->get('Version'),
                'parent'           => $theme->parent() ? $theme->parent()->get_stylesheet() : null,
                'is_active'        => $stylesheet === $active_stylesheet,
                'update_available' => $has_update,
                'new_version'      => $new_version,
            ];
        }

        return ['themes' => $rows];
    }
}
