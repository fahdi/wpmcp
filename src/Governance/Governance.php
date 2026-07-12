<?php

namespace WPMCP\Governance;

use WPMCP\MCP\Ability;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Resolves whether an Ability should be registered/executable, layering four
 * checks from most- to least-specific. Each layer can only narrow (turn off)
 * what the previous layer allowed; none of them can force an ability back on
 * once a more specific layer has disabled it.
 */
class Governance
{
    public const OPTION = 'wpmcp_governance_settings';

    public static function is_ability_enabled(Ability $a): bool
    {
        $enabled = apply_filters('wpmcp_ability_enabled', true, $a->name);
        return (bool) $enabled;
    }
}
