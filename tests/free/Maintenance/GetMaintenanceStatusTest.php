<?php

namespace WPMCP\Tests\Free\Maintenance;

use WPMCP\Tools\Maintenance\Get_Maintenance_Status;

class GetMaintenanceStatusTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        delete_option('wpmcp_maintenance');
        parent::tearDown();
    }

    public function test_reports_disabled_when_option_is_absent(): void
    {
        delete_option('wpmcp_maintenance');

        $out = (new Get_Maintenance_Status())->handle([]);

        $this->assertFalse($out['enabled']);
        $this->assertSame('', $out['message']);
    }

    public function test_reports_enabled_and_message_from_the_option(): void
    {
        update_option('wpmcp_maintenance', [
            'enabled'     => true,
            'message'     => 'Back soon.',
            'retry_after' => 3600,
        ]);

        $out = (new Get_Maintenance_Status())->handle([]);

        $this->assertTrue($out['enabled']);
        $this->assertSame('Back soon.', $out['message']);
        $this->assertSame(3600, $out['retry_after']);
    }
}
