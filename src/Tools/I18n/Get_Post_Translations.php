<?php

namespace WPMCP\Tools\I18n;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: return a post's translations (the translated post id and title,
 * keyed by language code) via the active multilingual plugin, through
 * I18n_Adapter. Reads have nothing to roll back, so this never touches
 * Safe_Mutation.
 */
class Get_Post_Translations
{
    public function handle(array $args): array
    {
        $post_id = (int) ($args['post_id'] ?? 0);
        if ($post_id <= 0) {
            throw new \InvalidArgumentException('A post id is required.');
        }

        return [
            'post_id'      => $post_id,
            'translations' => I18n_Adapter::get_post_translations($post_id),
        ];
    }
}
