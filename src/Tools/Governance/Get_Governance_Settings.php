<?php

namespace WPMCP\Tools\Governance;

use WPMCP\Governance\Governance;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: returns the three stored governance toggle maps (ability,
 * domain, operation) exactly as Governance stores them. Never touches
 * Safe_Mutation; reads have nothing to roll back.
 */
class Get_Governance_Settings
{
    public function handle(array $args): array
    {
        return [
            'ability'   => Governance::ability_toggles(),
            'domain'    => Governance::domain_toggles(),
            'operation' => Governance::operation_toggles(),
        ];
    }
}
