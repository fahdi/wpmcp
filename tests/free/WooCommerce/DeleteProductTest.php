<?php

namespace WPMCP\Tests\Free\WooCommerce;

use WPMCP\Tools\WooCommerce\Delete_Product;
use WPMCP\Tools\Rollback_Operation;
use WPMCP\Safety\Snapshot_Store;

class DeleteProductTest extends \WP_UnitTestCase
{
    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();
        Snapshot_Store::install();
        add_filter('wpmcp_enable_delete_product', '__return_true');
    }

    protected function tearDown(): void
    {
        remove_filter('wpmcp_enable_delete_product', '__return_true');
        foreach ($this->created as $id) {
            wp_delete_post($id, true);
        }
        $this->created = [];
        parent::tearDown();
    }

    private function product(): int
    {
        $product = new \WC_Product_Simple();
        $product->set_name('Clay Pot');
        $product->set_regular_price('8.00');
        $id = $product->save();
        $this->created[] = $id;
        return $id;
    }

    public function test_delete_disabled_by_default(): void
    {
        if (! wpmcp_woocommerce_active()) {
            $this->markTestSkipped('WooCommerce not active');
        }

        remove_filter('wpmcp_enable_delete_product', '__return_true');
        $id = $this->product();

        try {
            (new Delete_Product())->handle(['id' => $id, 'confirm' => true]);
            $this->fail('Expected a refusal while the tool is disabled.');
        } catch (\RuntimeException $e) {
            $this->assertNotNull(wc_get_product($id), 'Product must be untouched.');
        }
    }

    public function test_delete_requires_confirm(): void
    {
        if (! wpmcp_woocommerce_active()) {
            $this->markTestSkipped('WooCommerce not active');
        }

        $id = $this->product();
        $this->expectException(\InvalidArgumentException::class);
        (new Delete_Product())->handle(['id' => $id]);
    }

    public function test_missing_product_throws(): void
    {
        if (! wpmcp_woocommerce_active()) {
            $this->markTestSkipped('WooCommerce not active');
        }

        $this->expectException(\InvalidArgumentException::class);
        (new Delete_Product())->handle(['id' => 999999, 'confirm' => true]);
    }

    public function test_trash_delete_is_reversible(): void
    {
        if (! wpmcp_woocommerce_active()) {
            $this->markTestSkipped('WooCommerce not active');
        }

        $id  = $this->product();
        $out = (new Delete_Product())->handle(['id' => $id, 'confirm' => true]);

        $this->assertArrayHasKey('operation_id', $out);
        $this->assertSame('trash', get_post_status($id));

        $rolled_back = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($rolled_back['restored']);
        $this->assertSame('publish', get_post_status($id));
    }

    public function test_force_delete_is_recoverable_at_same_id(): void
    {
        if (! wpmcp_woocommerce_active()) {
            $this->markTestSkipped('WooCommerce not active');
        }

        $id  = $this->product();
        $out = (new Delete_Product())->handle(['id' => $id, 'confirm' => true, 'force' => true]);
        $this->assertNull(get_post($id));

        $rolled_back = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($rolled_back['restored']);

        $restored = wc_get_product($id);
        $this->assertNotFalse($restored);
        $this->assertSame('Clay Pot', $restored->get_name());
        $this->assertSame('8.00', $restored->get_regular_price());
    }
}
