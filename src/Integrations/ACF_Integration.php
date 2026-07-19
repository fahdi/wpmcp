<?php

namespace WPMCP\Integrations;

use WPMCP\Tools\ACF\Get_Fields;
use WPMCP\Tools\ACF\List_Field_Groups;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Reference integration proving the dispatcher framework end-to-end (#65):
 * ACF field read/write behind a single wpmcp/acf-read + wpmcp/acf-write
 * pair.
 *
 * Read operations reuse the existing flat-tool handlers (Get_Fields,
 * List_Field_Groups) unchanged. The update-fields write op mirrors the flat
 * wpmcp/update-fields tool's posture exactly: default-off, opted in through
 * the SAME wpmcp_enable_acf_write filter (a site that has already enabled
 * ACF writes gets the dispatcher write too, with no second switch), and
 * snapshotted on the post target — ACF values are ordinary postmeta, so the
 * standard post snapshot captures them and rollback-operation restores them
 * exactly.
 *
 * Unlike the flat ACF tool group (which skips registration when ACF is
 * absent), the dispatcher pair registers unconditionally: availability is a
 * call-time concern for dispatchers, and a missing host plugin yields a
 * clean integration_unavailable error instead of an absent tool.
 */
class ACF_Integration extends Integration_Dispatcher
{
    public function integration(): string
    {
        return 'acf';
    }

    public function is_available(): bool
    {
        return function_exists('acf_get_field_groups')
            && function_exists('get_fields')
            && function_exists('update_field');
    }

    protected function summary(): string
    {
        return 'Advanced Custom Fields (field groups and per-post field values)';
    }

    protected function operations(): array
    {
        return [
            'list-field-groups' => [
                'mode'         => 'read',
                'description'  => 'List registered ACF field groups: key, title, a flattened summary of their location rules, and whether each is active',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [],
                ],
                'handler'      => fn (array $args) => (new List_Field_Groups())->handle($args),
            ],
            'get-fields'        => [
                'mode'         => 'read',
                'description'  => 'Read a post\'s ACF field values, keyed by field name, via get_fields()',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'post_id' => [ 'type' => 'integer', 'minimum' => 1 ],
                    ],
                    'required'   => [ 'post_id' ],
                ],
                'handler'      => fn (array $args) => (new Get_Fields())->handle($args),
            ],
            'update-fields'     => [
                'mode'               => 'write',
                'description'        => 'Set one or more ACF field values on a post via update_field(). Snapshotted on the post target; restorable with rollback-operation. Disabled by default (site opts in via the wpmcp_enable_acf_write filter)',
                'enabled_by_default' => (bool) apply_filters('wpmcp_enable_acf_write', false),
                'input_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'post_id' => [ 'type' => 'integer', 'minimum' => 1 ],
                        'fields'  => [ 'type' => 'object', 'minProperties' => 1 ],
                    ],
                    'required'   => [ 'post_id', 'fields' ],
                ],
                'handler'            => function (array $args): array {
                    $post_id = (int) $args['post_id'];
                    foreach ((array) $args['fields'] as $selector => $value) {
                        update_field((string) $selector, $value, $post_id);
                    }
                    $fields = get_fields($post_id);
                    return [ 'post_id' => $post_id, 'fields' => is_array($fields) ? $fields : [] ];
                },
                'snapshot'           => fn (array $args) => [
                    'object_type' => 'post',
                    'object_id'   => (int) $args['post_id'],
                ],
            ],
        ];
    }
}
