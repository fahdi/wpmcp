<?php

namespace WPMCP\Tools\Maintenance;

use WPMCP\Safety\Safe_Mutation;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Turn maintenance mode on by writing the wpmcp_maintenance option
 * (enabled=true, message, retry_after seconds).
 *
 * Routed through Safe_Mutation::run() with object_type 'option' on the
 * 'wpmcp_maintenance' option name, so the prior state (off, or a previous
 * on-state) is snapshotted and rollback-operation can restore it. Reuses
 * the existing option object type; no safety-core change.
 */
class Enable_Maintenance
{
    private const DEFAULT_MESSAGE     = 'This site is currently undergoing scheduled maintenance. Please check back soon.';
    private const DEFAULT_RETRY_AFTER = 3600;

    public function handle(array $args): array
    {
        $message     = isset($args['message']) ? sanitize_text_field((string) $args['message']) : self::DEFAULT_MESSAGE;
        $retry_after = isset($args['retry_after']) ? max(0, (int) $args['retry_after']) : self::DEFAULT_RETRY_AFTER;

        if ('' === $message) {
            $message = self::DEFAULT_MESSAGE;
        }

        $value = [
            'enabled'     => true,
            'message'     => $message,
            'retry_after' => $retry_after,
        ];

        $out = Safe_Mutation::run(
            [
                'object_type' => 'option',
                'object_id'   => 'wpmcp_maintenance',
                'session_id'  => (string) ($args['session_id'] ?? 'default'),
                'tool_name'   => 'enable-maintenance',
                'args'        => $args,
            ],
            function () use ($value): void {
                update_option('wpmcp_maintenance', $value);
            }
        );

        return [
            'enabled'      => true,
            'message'      => $message,
            'retry_after'  => $retry_after,
            'operation_id' => $out['operation_id'],
            'recoverable'  => true,
        ];
    }
}
