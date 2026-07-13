<?php

namespace WPMCP\Tools\Maintenance;

use WPMCP\Safety\Safe_Mutation;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Turn maintenance mode off by setting enabled=false on the
 * wpmcp_maintenance option. The message and retry_after are left in place
 * (rather than deleting the option outright) so re-enabling recalls the
 * last configured message.
 *
 * Routed through Safe_Mutation::run() with object_type 'option' on the
 * 'wpmcp_maintenance' option name, so the prior (on) state is snapshotted
 * and rollback-operation can restore it. Reuses the existing option object
 * type; no safety-core change.
 */
class Disable_Maintenance
{
    public function handle(array $args): array
    {
        $current = get_option('wpmcp_maintenance');
        if (! is_array($current)) {
            $current = [];
        }

        $value               = $current;
        $value['enabled']    = false;
        $value['message']    = (string) ($current['message'] ?? '');
        $value['retry_after'] = (int) ($current['retry_after'] ?? 0);

        $out = Safe_Mutation::run(
            [
                'object_type' => 'option',
                'object_id'   => 'wpmcp_maintenance',
                'session_id'  => (string) ($args['session_id'] ?? 'default'),
                'tool_name'   => 'disable-maintenance',
                'args'        => $args,
            ],
            function () use ($value): void {
                update_option('wpmcp_maintenance', $value);
            }
        );

        return [
            'enabled'      => false,
            'operation_id' => $out['operation_id'],
            'recoverable'  => true,
        ];
    }
}
