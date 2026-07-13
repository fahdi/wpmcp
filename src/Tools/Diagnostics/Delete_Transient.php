<?php

namespace WPMCP\Tools\Diagnostics;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Delete a single named transient via delete_transient().
 *
 * NOT routed through Safe_Mutation and does NOT touch the safety core:
 * transients are cache-like, regenerable data with no meaningful
 * before-image to restore, the same reasoning Clear_Cache already documents
 * for flushing every transient at once. Deleting one by name is simply a
 * narrower version of that same safe, idempotent operation.
 */
class Delete_Transient
{
    public function handle(array $args): array
    {
        $name = isset($args['name']) ? (string) $args['name'] : '';
        if ('' === $name) {
            throw new \InvalidArgumentException('A transient name is required.');
        }

        return ['name' => $name, 'deleted' => delete_transient($name)];
    }
}
