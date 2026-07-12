<?php

namespace WPMCP\Tools\Packages;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Shared guardrails for the plugin/theme package tools.
 *
 * Protected packages (this plugin itself, and Elementor free/pro) can never
 * be deactivated or deleted through these tools: an agent that disables its
 * own delivery mechanism, or the site's page builder, would strand the site
 * with no way to recover other than direct server access.
 */
class Package_Guard
{
    /** Plugin file paths (relative to the plugins dir) that may never be deactivated or deleted. */
    private const PROTECTED_PLUGINS = [
        'wpmcp/wpmcp.php',
        'elementor/elementor.php',
        'elementor-pro/elementor-pro.php',
    ];

    public static function is_protected_plugin(string $plugin_file): bool
    {
        return in_array($plugin_file, self::PROTECTED_PLUGINS, true);
    }

    /**
     * Package installs/updates/deletes require WordPress to be able to write
     * to the filesystem directly (no FTP/SSH credential prompt). If the
     * detected method is anything other than 'direct', these tools refuse
     * rather than attempting a credential-based filesystem connection.
     */
    public static function filesystem_ready(): bool
    {
        if (! function_exists('get_filesystem_method')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        return 'direct' === get_filesystem_method();
    }
}
