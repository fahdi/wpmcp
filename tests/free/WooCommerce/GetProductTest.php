<?php

namespace WPMCP\Tests\Free\WooCommerce;

use WPMCP\Tools\WooCommerce\Get_Product;

class GetProductTest extends \WP_UnitTestCase
{
    private array $created = [];

    protected function tearDown(): void
    {
        foreach ($this->created as $id) {
            wp_delete_post($id, true);
        }
        $this->created = [];
        parent::tearDown();
    }

    private function product(): int
    {
        $product = new \WC_Product_Simple();
        $product->set_name('Brass Lamp');
        $product->set_regular_price('45.00');
        $product->set_description('A hand-polished brass lamp.');
        $id = $product->save();
        $this->created[] = $id;
        return $id;
    }

    public function test_returns_product_detail(): void
    {
        if (! wpmcp_woocommerce_active()) {
            $this->markTestSkipped('WooCommerce not active');
        }

        $id  = $this->product();
        $out = (new Get_Product())->handle(['id' => $id]);

        $this->assertSame($id, $out['id']);
        $this->assertSame('Brass Lamp', $out['name']);
        $this->assertSame('45.00', $out['regular_price']);
        $this->assertSame('A hand-polished brass lamp.', $out['description']);
        $this->assertArrayHasKey('categories', $out);
        $this->assertArrayHasKey('tags', $out);
    }

    public function test_missing_product_throws(): void
    {
        if (! wpmcp_woocommerce_active()) {
            $this->markTestSkipped('WooCommerce not active');
        }

        $this->expectException(\RuntimeException::class);
        (new Get_Product())->handle(['id' => 999999]);
    }

    public function test_requires_id(): void
    {
        if (! wpmcp_woocommerce_active()) {
            $this->markTestSkipped('WooCommerce not active');
        }

        $this->expectException(\InvalidArgumentException::class);
        (new Get_Product())->handle([]);
    }
}
