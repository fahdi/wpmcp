<?php

namespace WPMCP\Tools\Blocks;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: enumerate the block types registered with
 * WP_Block_Type_Registry::get_instance()->get_all_registered(), each reduced
 * to its name, title, category, whether it renders dynamically (has a
 * render_callback), and its declared attribute names.
 *
 * An optional 'category' (exact match) and/or 'search' (substring match
 * against the block name) narrow the result.
 */
class List_Block_Types
{
    public function handle(array $args): array
    {
        $category = isset($args['category']) ? (string) $args['category'] : '';
        $search   = isset($args['search']) ? (string) $args['search'] : '';

        $registry = \WP_Block_Type_Registry::get_instance();
        $all      = $registry->get_all_registered();

        $names = array_keys($all);
        sort($names);

        $block_types = [];
        foreach ($names as $name) {
            $block_type = $all[ $name ];

            if ('' !== $search && false === strpos($name, $search)) {
                continue;
            }
            if ('' !== $category && $category !== (string) ($block_type->category ?? '')) {
                continue;
            }

            $block_types[] = [
                'name'       => $name,
                'title'      => (string) ($block_type->title ?? ''),
                'category'   => (string) ($block_type->category ?? ''),
                'is_dynamic' => is_callable($block_type->render_callback ?? null),
                'attributes' => is_array($block_type->attributes ?? null)
                    ? array_keys($block_type->attributes)
                    : [],
            ];
        }

        return ['block_types' => $block_types];
    }
}
