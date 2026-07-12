<?php

namespace WPMCP\Tools\Content;

use WPMCP\Safety\Safe_Mutation;

if (! defined('ABSPATH')) {
    exit;
}

class Set_Post_Terms
{
    public function handle(array $args): array
    {
        $post_id  = (int) ($args['post_id'] ?? 0);
        $taxonomy = sanitize_key((string) ($args['taxonomy'] ?? ''));
        $post     = $post_id ? get_post($post_id) : null;
        if (! $post) {
            throw new \InvalidArgumentException('Post not found');
        }

        $mode  = $args['mode'] ?? 'replace';
        $mode  = in_array($mode, ['replace', 'append', 'remove'], true) ? $mode : 'replace';
        $terms = array_values((array) ($args['terms'] ?? []));

        $out = Safe_Mutation::run(
            [
                'object_type' => 'post',
                'object_id'   => $post_id,
                'session_id'  => (string) ($args['session_id'] ?? 'default'),
                'tool_name'   => 'set-post-terms',
                'args'        => $args,
            ],
            function () use ($post_id, $taxonomy, $terms, $mode) {
                if ('remove' === $mode) {
                    wp_remove_object_terms($post_id, $terms, $taxonomy);
                } else {
                    wp_set_object_terms($post_id, $terms, $taxonomy, 'append' === $mode);
                }
                return true;
            }
        );

        $current = [];
        $term_objects = get_the_terms($post_id, $taxonomy);
        if (is_array($term_objects)) {
            foreach ($term_objects as $t) {
                $current[] = ['term_id' => (int) $t->term_id, 'name' => (string) $t->name, 'slug' => (string) $t->slug];
            }
        }

        return [
            'operation_id' => $out['operation_id'],
            'post_id'      => $post_id,
            'taxonomy'     => $taxonomy,
            'terms'        => $current,
        ];
    }
}
