<?php

namespace WPMCP\Tools\WooCommerce;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: list WooCommerce product categories (the product_cat taxonomy)
 * as summary rows. Reads have nothing to roll back, so this never touches
 * Safe_Mutation.
 */
class List_Product_Categories
{
    public function handle(array $args): array
    {
        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => ! empty($args['hide_empty']),
        ]);

        $rows = [];
        foreach ((is_array($terms) ? $terms : []) as $term) {
            $rows[] = [
                'id'     => (int) $term->term_id,
                'name'   => $term->name,
                'slug'   => $term->slug,
                'parent' => (int) $term->parent,
                'count'  => (int) $term->count,
            ];
        }

        return ['categories' => $rows];
    }
}
