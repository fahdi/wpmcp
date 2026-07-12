<?php

namespace WPMCP\Safety;

if (! defined('ABSPATH')) {
    exit;
}

class Snapshot
{
    public static function capture(string $object_type, int $object_id): array
    {
        $post = get_post($object_id, ARRAY_A);
        return [
            'object_type' => $object_type,
            'object_id'   => $object_id,
            'data'        => [
                // Full row (all columns), not a hand-picked subset: a partial
                // capture means resurrection after a force-delete rebuilds
                // the missing columns from wp_insert_post()'s defaults
                // (post_type becomes 'post', post_author/post_parent/post_name/
                // dates/menu_order/etc are lost). See apply_snapshot() for how
                // the in-place vs resurrection restore paths use this.
                'post'     => $post ?: null,
                'meta'     => get_post_meta($object_id),
                'terms'    => $post ? self::capture_terms($object_id, $post['post_type']) : [],
                // Only needed for the force-delete -> resurrect path: wp_delete_post($id, true)
                // destroys comments + commentmeta, which have no equivalent in
                // the trash/in-place-update paths.
                'comments' => $post ? self::capture_comments($object_id) : [],
            ],
        ];
    }

    /** Comments (with their commentmeta) attached to the post, for resurrection after a force-delete. */
    private static function capture_comments(int $post_id): array
    {
        $comments = get_comments(['post_id' => $post_id, 'status' => 'all', 'orderby' => 'comment_ID', 'order' => 'ASC']);
        $out = [];
        foreach ($comments as $comment) {
            $data = $comment->to_array();
            $data['meta'] = get_comment_meta((int) $comment->comment_ID);
            $out[] = $data;
        }
        return $out;
    }

    /** Map of taxonomy => term IDs currently assigned to the post, for terms rollback. */
    private static function capture_terms(int $post_id, string $post_type): array
    {
        $terms = [];
        foreach ((array) get_object_taxonomies($post_type) as $taxonomy) {
            $ids = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
            if (is_array($ids)) {
                $terms[ $taxonomy ] = $ids;
            }
        }
        return $terms;
    }

    public static function serialize(array $before): string
    {
        return gzencode(wp_json_encode($before));
    }

    public static function unserialize(string $blob): array
    {
        return json_decode(gzdecode($blob), true);
    }
}
