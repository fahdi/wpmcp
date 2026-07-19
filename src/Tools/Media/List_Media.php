<?php

namespace WPMCP\Tools\Media;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * list-media (issue #64): enumerate the Media Library with type/date/search
 * filters and paging. Read-only; complements get-media (single attachment).
 */
class List_Media
{
    public function handle(array $args): array
    {
        $per_page = min(100, max(1, (int) ($args['per_page'] ?? 20)));
        $page     = max(1, (int) ($args['page'] ?? 1));

        $query_args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => ['date' => 'DESC', 'ID' => 'DESC'],
            'no_found_rows'  => false,
        ];

        // 'image' matches every image/* subtype; 'image/png' matches exactly.
        $type = trim((string) ($args['type'] ?? ''));
        if ('' !== $type) {
            $query_args['post_mime_type'] = $type;
        }

        $search = trim((string) ($args['search'] ?? ''));
        if ('' !== $search) {
            $query_args['s'] = $search;
        }

        $date_query = [];
        if (! empty($args['after'])) {
            $date_query['after'] = (string) $args['after'];
        }
        if (! empty($args['before'])) {
            $date_query['before'] = (string) $args['before'];
        }
        if ($date_query) {
            $date_query['inclusive']  = true;
            $query_args['date_query'] = [ $date_query ];
        }

        $query = new \WP_Query($query_args);

        $items = [];
        foreach ($query->posts as $post) {
            $meta    = wp_get_attachment_metadata($post->ID);
            $meta    = is_array($meta) ? $meta : [];
            $items[] = [
                'media_id'    => (int) $post->ID,
                'title'       => (string) $post->post_title,
                'url'         => (string) wp_get_attachment_url($post->ID),
                'mime_type'   => (string) $post->post_mime_type,
                'alt'         => (string) get_post_meta($post->ID, '_wp_attachment_image_alt', true),
                'date'        => (string) $post->post_date,
                'post_parent' => (int) $post->post_parent,
                'width'       => isset($meta['width']) ? (int) $meta['width'] : 0,
                'height'      => isset($meta['height']) ? (int) $meta['height'] : 0,
            ];
        }

        return [
            'items'    => $items,
            'total'    => (int) $query->found_posts,
            'pages'    => max(1, (int) $query->max_num_pages),
            'page'     => $page,
            'per_page' => $per_page,
        ];
    }
}
