<?php

namespace WPMCP\Tests\Free\WooCommerce;

use WPMCP\Safety\Snapshot;
use WPMCP\Safety\Rollback_Service;

/**
 * Exercises the additive 'wc_order' object type in the safety core directly:
 * capturing an order's prior status and restoring it, working uniformly under
 * HPOS or the legacy CPT because it goes through the WC_Order CRUD getters and
 * setters, not raw storage.
 */
class OrderSnapshotTest extends \WP_UnitTestCase
{
    private array $created = [];

    protected function tearDown(): void
    {
        foreach ($this->created as $id) {
            $order = wc_get_order($id);
            if ($order) {
                $order->delete(true);
            }
        }
        $this->created = [];
        parent::tearDown();
    }

    private function order(string $status = 'pending'): int
    {
        $order = wc_create_order();
        $order->set_status($status);
        $id = $order->save();
        $this->created[] = $id;
        return $id;
    }

    public function test_captures_order_status(): void
    {
        if (! wpmcp_woocommerce_active()) {
            $this->markTestSkipped('WooCommerce not active');
        }

        $id       = $this->order('pending');
        $snapshot = Snapshot::capture('wc_order', $id);

        $this->assertSame('wc_order', $snapshot['object_type']);
        $this->assertSame($id, $snapshot['object_id']);
        $this->assertSame('pending', $snapshot['data']['status']);
    }

    public function test_rollback_restores_prior_status(): void
    {
        if (! wpmcp_woocommerce_active()) {
            $this->markTestSkipped('WooCommerce not active');
        }

        $id       = $this->order('pending');
        $snapshot = Snapshot::capture('wc_order', $id);

        $order = wc_get_order($id);
        $order->set_status('completed');
        $order->save();
        $this->assertSame('completed', wc_get_order($id)->get_status());

        Rollback_Service::apply_snapshot($snapshot);

        $this->assertSame('pending', wc_get_order($id)->get_status());
    }
}
