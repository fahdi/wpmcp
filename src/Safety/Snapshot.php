<?php

namespace WPMCP\Safety;

if (! defined('ABSPATH')) {
    exit;
}

class Snapshot
{
    /**
     * Capture the pre-mutation state of an object so it can later be restored
     * by Rollback_Service::apply_snapshot(). The object identifier's type
     * depends on $object_type: posts (and attachments, which are posts) are
     * identified by their integer ID; options are identified by their string
     * name. Dispatching here on $object_type, rather than on the PHP type of
     * $object_id, keeps the door open for future object types (e.g. users)
     * that might also use an int identifier but need different capture logic.
     */
    public static function capture(string $object_type, $object_id): array
    {
        if ('option' === $object_type) {
            return self::capture_option((string) $object_id);
        }
        return self::capture_post($object_id);
    }

    private static function capture_post(int $object_id): array
    {
        $post = get_post($object_id, ARRAY_A);
        return [
            'object_type' => 'post',
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

    /**
     * Options have no equivalent of trash/force-delete: a write either
     * changes an existing option's value or, if it didn't exist yet, an
     * update introduces one. 'existed' records which case this was so
     * Rollback_Service can decide between update_option() (put the old
     * value back) and delete_option() (remove the option entirely, since it
     * wasn't there before the mutation).
     */
    private static function capture_option(string $name): array
    {
        return [
            'object_type' => 'option',
            'object_id'   => $name,
            'data'        => [
                'name'    => $name,
                'value'   => get_option($name),
                'existed' => self::option_exists($name),
            ],
        ];
    }

    /** True if $name has a row in the options table, distinguishing "unset" from a falsy stored value. */
    private static function option_exists(string $name): bool
    {
        $sentinel = '__wpmcp_missing__' . $name;
        return get_option($name, $sentinel) !== $sentinel;
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
