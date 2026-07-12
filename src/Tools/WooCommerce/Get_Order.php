<?php

namespace WPMCP\Tools\WooCommerce;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: return full detail for one WooCommerce order via wc_get_order
 * (HPOS- and CPT-safe). Reads have nothing to roll back, so this never touches
 * Safe_Mutation.
 */
class Get_Order
{
    public function handle(array $args): array
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            throw new \InvalidArgumentException('An order id is required.');
        }

        $order = wc_get_order($id);
        if (! $order) {
            throw new \RuntimeException('Order not found.');
        }

        return Order_View::detail($order);
    }
}
