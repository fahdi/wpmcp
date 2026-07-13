<?php

namespace WPMCP\Tests\Free\Maintenance;

use WPMCP\Tools\Maintenance\Disable_Maintenance;
use WPMCP\Tools\Rollback_Operation;
use WPMCP\Safety\Snapshot_Store;

class DisableMaintenanceTest extends \WP_UnitTestCase
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

    public function test_disables_maintenance_mode(): void
    {
        update_option('wpmcp_maintenance', [
            'enabled'     => true,
            'message'     => 'Brb.',
            'retry_after' => 3600,
        ]);

        $out = (new Disable_Maintenance())->handle([]);

        $option = get_option('wpmcp_maintenance');
        $this->assertFalse($option['enabled']);
        $this->assertFalse($out['enabled']);
        $this->assertArrayHasKey('operation_id', $out);
    }

    public function test_disable_is_undoable_and_rollback_restores_prior_on_state(): void
    {
        update_option('wpmcp_maintenance', [
            'enabled'     => true,
            'message'     => 'Brb.',
            'retry_after' => 1800,
        ]);

        $out = (new Disable_Maintenance())->handle([]);
        $this->assertFalse(get_option('wpmcp_maintenance')['enabled']);

        $rolled_back = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($rolled_back['restored']);

        $option = get_option('wpmcp_maintenance');
        $this->assertTrue($option['enabled']);
        $this->assertSame('Brb.', $option['message']);
        $this->assertSame(1800, $option['retry_after']);
    }
}
