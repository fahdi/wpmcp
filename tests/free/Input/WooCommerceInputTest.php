<?php

namespace WPMCP\Tests\Free\Input;

use WPMCP\Tools\WooCommerce\Create_Product;
use WPMCP\Tools\WooCommerce\Update_Product;
use WPMCP\Tools\WooCommerce\Update_Order_Status;

/**
 * Input-boundary tests for the WooCommerce domain: missing required args,
 * invalid/out-of-range ids, and unknown enum-like status values must all
 * fail cleanly (InvalidArgumentException/RuntimeException), never a fatal
 * or a silent no-op write.
 */
class WooCommerceInputTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! wpmcp_woocommerce_active()) {
            $this->markTestSkipped('WooCommerce not active');
        }
    }

    public function test_create_product_rejects_missing_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Create_Product())->handle([]);
    }

    public function test_create_product_rejects_empty_string_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Create_Product())->handle(['name' => '   ']);
    }

    public function test_create_product_coerces_a_non_numeric_price_to_empty_rather_than_fatal(): void
    {
        // WooCommerce's setter sanitizes non-numeric price strings down to
        // an empty/zero value instead of throwing; the tool must not fatal
        // and must return a coherent product either way.
        $result = (new Create_Product())->handle(['name' => 'Widget', 'regular_price' => 'not-a-number']);

        $this->assertArrayHasKey('id', $result);
        wc_get_product($result['id'])->delete(true);
    }

    public function test_update_product_rejects_missing_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Update_Product())->handle(['name' => 'x']);
    }

    public function test_update_product_rejects_zero_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Update_Product())->handle(['id' => 0, 'name' => 'x']);
    }

    public function test_update_product_rejects_negative_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Update_Product())->handle(['id' => -1, 'name' => 'x']);
    }

    public function test_update_product_rejects_nonexistent_id(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Update_Product())->handle(['id' => 999999999, 'name' => 'x']);
    }

    public function test_update_order_status_rejects_missing_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Update_Order_Status())->handle(['status' => 'completed']);
    }

    public function test_update_order_status_rejects_missing_status(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Update_Order_Status())->handle(['id' => 1]);
    }

    public function test_update_order_status_rejects_unknown_status(): void
    {
        $order = new \WC_Order();
        $order->save();

        $this->expectException(\InvalidArgumentException::class);
        try {
            (new Update_Order_Status())->handle(['id' => $order->get_id(), 'status' => 'not-a-real-status']);
        } finally {
            $order->delete(true);
        }
    }

    public function test_update_order_status_rejects_nonexistent_order(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Update_Order_Status())->handle(['id' => 999999999, 'status' => 'completed']);
    }
}
