<?php

namespace WPMCP\Tools\Code;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Security core for the guarded PHP snippet executor (issue #45). This is
 * the single most dangerous feature in the plugin: running arbitrary PHP is
 * remote code execution by definition, and it cannot be sandboxed in-process
 * or made undoable. Every guard Run_Php_Snippet relies on lives here as a
 * pure, independently testable check, mirroring Wp_Cli_Guard's shape:
 * enable/disable and environment refusal live here; Run_Php_Snippet composes
 * these checks and is the only caller that ever actually evaluates a
 * snippet.
 *
 * This tool is the ONE explicit escape hatch outside the snapshot/rollback
 * safety model: its effects are not captured and not undoable, and enabling
 * it grants RCE to anyone who can call it with manage_options. The product's
 * "AI physically can't wreck your site" promise holds only because this is
 * default-off, dev-only, and must be deliberately enabled.
 */
class Php_Snippet_Guard
{
    /**
     * Whether PHP snippet execution is enabled at all. Two opt-in seams,
     * either sufficient: the WPMCP_ALLOW_PHP_EXEC constant (for
     * wp-config.php) and the wpmcp_allow_php_exec filter (programmatic
     * control, also what tests use). Default (neither set) is OFF, matching
     * Wp_Cli_Guard::is_enabled() and OAuth_Config::is_enabled(). A disabled
     * install can never eval anything: this is the first and most important
     * guard.
     */
    public static function is_enabled(): bool
    {
        $default = defined('WPMCP_ALLOW_PHP_EXEC') && WPMCP_ALLOW_PHP_EXEC;

        return (bool) apply_filters('wpmcp_allow_php_exec', $default);
    }
}
