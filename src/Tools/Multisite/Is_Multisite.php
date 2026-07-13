<?php

namespace WPMCP\Tools\Multisite;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: report whether this WordPress install is part of a multisite
 * network. Unlike the other Multisite tools, this one is registered
 * unconditionally (see Plugin.php): a caller has to be able to ask "is this
 * a network at all" before it makes sense to gate the rest of the tool group
 * on the answer, so this single tool is always safe and always present.
 */
class Is_Multisite
{
    public function handle(array $args): array
    {
        return ['is_multisite' => Multisite_Adapter::is_network()];
    }
}
