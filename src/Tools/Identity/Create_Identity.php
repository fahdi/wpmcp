<?php

namespace WPMCP\Tools\Identity;

use WPMCP\Identity\Identity_Store;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Create (or overwrite, by name) a scoped identity. 'name' is required and
 * must be non-empty; 'domains', 'operations', 'abilities' (each string[])
 * and 'mode' ('allow'|'deny') are all optional and default per
 * Identity_Store::create(). Plain option write, no Safe_Mutation/rollback.
 */
class Create_Identity
{
    public function handle(array $args): array
    {
        $name = isset($args['name']) ? (string) $args['name'] : '';
        if ('' === $name) {
            throw new \InvalidArgumentException('An identity name is required.');
        }

        return Identity_Store::create($name, $args);
    }
}
