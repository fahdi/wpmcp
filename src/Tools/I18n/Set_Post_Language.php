<?php

namespace WPMCP\Tools\I18n;

use WPMCP\Safety\Safe_Mutation;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Assign a post to a language (by code) via I18n_Adapter::set_post_language().
 *
 * The write routes through Safe_Mutation with object_type 'post' and the post
 * id. For Polylang this is genuinely undoable: a post's language is stored as
 * a term in the 'language' taxonomy, and the existing post snapshot captures
 * every taxonomy assigned to the post's type (including 'language') before the
 * mutation, so a rollback-operation restores the prior language-term
 * assignment exactly, through the same engine that already covers posts. No
 * i18n-specific snapshot logic is needed.
 *
 * The WPML path is best-effort and untested against a real WPML install
 * (WPML is a paid plugin, not available from wordpress.org). WPML also stores
 * a post's language in its own icl_translations table rather than a taxonomy
 * term, so on WPML the post snapshot would not capture that row: rollback of a
 * WPML language assignment is therefore NOT guaranteed by the post snapshot.
 * This limitation is documented rather than papered over.
 */
class Set_Post_Language
{
    public function handle(array $args): array
    {
        $post_id = (int) ($args['post_id'] ?? 0);
        if ($post_id <= 0) {
            throw new \InvalidArgumentException('A post id is required.');
        }

        $language = (string) ($args['language'] ?? '');
        if ('' === $language) {
            throw new \InvalidArgumentException('A language code is required.');
        }

        $out = Safe_Mutation::run(
            [
                'object_type' => 'post',
                'object_id'   => $post_id,
                'session_id'  => (string) ($args['session_id'] ?? 'default'),
                'tool_name'   => 'set-post-language',
                'args'        => $args,
            ],
            function () use ($post_id, $language): void {
                I18n_Adapter::set_post_language($post_id, $language);
            }
        );

        return [
            'post_id'      => $post_id,
            'language'     => $language,
            'translations' => I18n_Adapter::get_post_translations($post_id),
            'operation_id' => $out['operation_id'],
            'recoverable'  => true,
        ];
    }
}
