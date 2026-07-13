<?php

namespace WPMCP\Tests\Free\Maintenance;

use WPMCP\Tools\Maintenance\Enable_Maintenance;
use WPMCP\Tools\Rollback_Operation;
use WPMCP\Safety\Snapshot_Store;

class EnableMaintenanceTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Snapshot_Store::install();
    }

    protected function tearDown(): void
    {
        delete_option('wpmcp_maintenance');
        parent::tearDown();
    }

    public function test_enables_maintenance_mode_with_default_message(): void
    {
        $out = (new Enable_Maintenance())->handle([]);

        $option = get_option('wpmcp_maintenance');
        $this->assertTrue($option['enabled']);
        $this->assertNotEmpty($option['message']);
        $this->assertArrayHasKey('operation_id', $out);
    }

    public function test_enables_maintenance_mode_with_custom_message_and_retry_after(): void
    {
        $out = (new Enable_Maintenance())->handle([
            'message'     => 'Down for upgrades.',
            'retry_after' => 1800,
        ]);

        $option = get_option('wpmcp_maintenance');
        $this->assertTrue($option['enabled']);
        $this->assertSame('Down for upgrades.', $option['message']);
        $this->assertSame(1800, $option['retry_after']);
        $this->assertSame('Down for upgrades.', $out['message']);
    }

    public function test_enable_is_undoable_and_rollback_restores_prior_off_state(): void
    {
        // Prior state: maintenance mode is off.
        delete_option('wpmcp_maintenance');

        $out = (new Enable_Maintenance())->handle(['message' => 'Brb.']);

        $option = get_option('wpmcp_maintenance');
        $this->assertTrue($option['enabled']);

        $rolled_back = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($rolled_back['restored']);

        $this->assertFalse(get_option('wpmcp_maintenance'));
    }
}
