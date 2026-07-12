<?php

namespace WPMCP\Tools\WooCommerce;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: return full detail for one WooCommerce product via the CRUD
 * layer (wc_get_product). Reads have nothing to roll back, so this never
 * touches Safe_Mutation.
 */
class Get_Product
{
    public function handle(array $args): array
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            throw new \InvalidArgumentException('A product id is required.');
        }

        $product = wc_get_product($id);
        if (! $product) {
            throw new \RuntimeException('Product not found.');
        }

        return Product_View::detail($product);
    }
}
