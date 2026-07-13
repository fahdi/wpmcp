<?php

namespace WPMCP\Tools\Identity;

use WPMCP\Identity\Identity_Store;

if (! defined('ABSPATH')) {
    exit;
}

/** Read-only: lists every registered identity, in creation/insertion order. */
class List_Identities
{
    public function handle(array $args): array
    {
        return ['identities' => Identity_Store::list()];
    }
}
