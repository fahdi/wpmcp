<?php

namespace WPMCP\Auth;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Resolves whether the OAuth 2.1 + Dynamic Client Registration subsystem
 * (issue #43) is active at all. Every OAuth endpoint and permission path
 * must consult this before granting anything, so a default install is
 * completely unaffected: no routes behave differently, no tokens are ever
 * accepted, until an integrator explicitly opts in.
 *
 * Two opt-in seams, mirroring Rate_Limiter::is_enabled(): a constant for
 * site owners who want to flip it on in wp-config.php, and a filter for
 * programmatic control (also what tests use). Either one enabling it is
 * sufficient; the default (neither set) is OFF.
 */
class OAuth_Config
{
    public static function is_enabled(): bool
    {
        $default = defined('WPMCP_OAUTH_ENABLED') && WPMCP_OAUTH_ENABLED;

        return (bool) apply_filters('wpmcp_oauth_enabled', $default);
    }
}
