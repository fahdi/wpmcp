<?php

namespace WPMCP\Tools\Maintenance;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: report whether maintenance mode is on and, when it is, the
 * configured message and retry-after seconds.
 *
 * Reads have nothing to roll back, so this never touches Safe_Mutation.
 */
class Get_Maintenance_Status
{
    public function handle(array $args): array
    {
        $option = get_option('wpmcp_maintenance');
        if (! is_array($option)) {
            $option = [];
        }

        return [
            'enabled'     => ! empty($option['enabled']),
            'message'     => (string) ($option['message'] ?? ''),
            'retry_after' => (int) ($option['retry_after'] ?? 0),
        ];
    }
}
