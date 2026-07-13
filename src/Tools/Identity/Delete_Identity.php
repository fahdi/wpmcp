<?php

namespace WPMCP\Tools\Identity;

use WPMCP\Identity\Identity_Store;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Delete an identity by name. Throws for entirely missing input (no name
 * given at all); returns a WP_Error (not a thrown exception) when the name
 * is well-formed but does not match any stored identity, mirroring
 * Get_Backup_Status's not-found handling for a lookup-by-key tool.
 */
class Delete_Identity
{
    public function handle(array $args)
    {
        $name = isset($args['name']) ? (string) $args['name'] : '';
        if ('' === $name) {
            throw new \InvalidArgumentException('An identity name is required.');
        }

        if (! Identity_Store::delete($name)) {
            return new \WP_Error(
                'wpmcp_identity_not_found',
                sprintf('No identity named "%s" was found.', $name)
            );
        }

        return ['deleted' => true, 'name' => $name];
    }
}
