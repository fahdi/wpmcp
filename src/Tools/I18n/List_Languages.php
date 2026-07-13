<?php

namespace WPMCP\Tools\I18n;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: list the site's configured languages (code, name, and which is
 * the default) via the active multilingual plugin, through I18n_Adapter.
 * Reads have nothing to roll back, so this never touches Safe_Mutation.
 */
class List_Languages
{
    public function handle(array $args): array
    {
        return ['languages' => I18n_Adapter::list_languages()];
    }
}
