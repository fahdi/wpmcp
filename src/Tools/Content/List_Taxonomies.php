<?php

namespace WPMCP\Tools\Content;

if (! defined('ABSPATH')) {
    exit;
}

class List_Taxonomies
{
    public function handle(array $args): array
    {
        $post_type = sanitize_key((string) ($args['post_type'] ?? ''));

        $objects = '' !== $post_type
            ? get_taxonomies(['object_type' => [$post_type]], 'objects')
            : get_taxonomies([], 'objects');

        $rows = [];
        foreach ($objects as $name => $object) {
            $rows[] = [
                'name'         => (string) $name,
                'label'        => (string) ($object->label ?? $name),
                'hierarchical' => (bool) ($object->hierarchical ?? false),
                'object_types' => array_values((array) ($object->object_type ?? [])),
            ];
        }

        return ['taxonomies' => $rows];
    }
}
