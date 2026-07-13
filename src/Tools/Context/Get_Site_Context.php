<?php

namespace WPMCP\Tools\Context;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only orientation payload for an agent connecting to this site: name,
 * URL, tagline, WordPress/PHP versions, and (in later behaviors) theme,
 * plugins, content model, users, locale, and integration capabilities.
 *
 * Deliberately excludes the admin email and any other secret-shaped value:
 * this tool is meant to be safe at a low capability bar (edit_posts), so
 * nothing it returns should require a stronger gate to see.
 */
class Get_Site_Context
{
    public function handle(array $args): array
    {
        $theme          = wp_get_theme();
        $active_plugins = (array) get_option('active_plugins', []);

        $post_types = [];
        foreach (get_post_types(['public' => true], 'objects') as $name => $object) {
            $post_types[] = [
                'name'  => (string) $name,
                'label' => (string) ($object->label ?? $name),
                'count' => (int) wp_count_posts($name)->publish,
            ];
        }

        $taxonomies = [];
        foreach (get_taxonomies(['public' => true], 'objects') as $name => $object) {
            $taxonomies[] = [
                'name'  => (string) $name,
                'label' => (string) ($object->label ?? $name),
            ];
        }

        return [
            'site' => [
                'name'    => get_bloginfo('name'),
                'url'     => home_url(),
                'tagline' => get_bloginfo('description'),
            ],
            'wordpress_version' => get_bloginfo('version'),
            'php_version'       => PHP_VERSION,
            'theme'             => [
                'name'     => $theme->get('Name'),
                'version'  => $theme->get('Version'),
                'is_child' => (bool) $theme->parent(),
            ],
            'plugins' => [
                'active_count' => count($active_plugins),
                'active_slugs' => $active_plugins,
            ],
            'post_types' => $post_types,
            'taxonomies' => $taxonomies,
        ];
    }
}
