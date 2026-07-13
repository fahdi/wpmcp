<?php

namespace WPMCP\Tools\I18n;

use WPMCP\Safety\Safe_Mutation;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Link a set of posts as translations of one another, given a list of
 * {language, post_id} pairs, via I18n_Adapter::link_post_translations().
 *
 * This relationship spans multiple posts, but Safe_Mutation snapshots a single
 * object. This tool snapshots only the FIRST (primary) post in the list, via
 * object_type 'post'. Rollback of the resulting operation therefore restores
 * ONLY the primary post's captured state (its 'language'/'post_translations'
 * taxonomy terms), NOT the other linked posts' states. That is a real,
 * documented limitation: undoing a link across N posts by restoring one post
 * is partial, and this tool does not claim otherwise. Callers who need to undo
 * the full group must re-link or manually reset the remaining posts.
 *
 * The WPML path is best-effort and untested against a real WPML install (WPML
 * is a paid plugin not available from wordpress.org).
 */
class Link_Post_Translations
{
    public function handle(array $args): array
    {
        $translations = $args['translations'] ?? null;
        if (! is_array($translations) || [] === $translations) {
            throw new \InvalidArgumentException('A non-empty translations list is required.');
        }

        $map = [];
        foreach ($translations as $entry) {
            $language = (string) ($entry['language'] ?? '');
            $post_id  = (int) ($entry['post_id'] ?? 0);
            if ('' === $language || $post_id <= 0) {
                throw new \InvalidArgumentException('Each translation needs a language and a post_id.');
            }
            $map[$language] = $post_id;
        }

        $primary_post_id = (int) reset($map);

        $out = Safe_Mutation::run(
            [
                'object_type' => 'post',
                'object_id'   => $primary_post_id,
                'session_id'  => (string) ($args['session_id'] ?? 'default'),
                'tool_name'   => 'link-post-translations',
                'args'        => $args,
            ],
            function () use ($map): void {
                I18n_Adapter::link_post_translations($map);
            }
        );

        return [
            'primary_post_id' => $primary_post_id,
            'translations'    => I18n_Adapter::get_post_translations($primary_post_id),
            'operation_id'    => $out['operation_id'],
            'recoverable'     => true,
        ];
    }
}
