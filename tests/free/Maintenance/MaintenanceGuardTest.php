<?php

namespace WPMCP\Tests\Free\Maintenance;

use WPMCP\Maintenance\Maintenance_Guard;

class MaintenanceGuardTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        delete_option('wpmcp_maintenance');
        wp_set_current_user(0);
        parent::tearDown();
    }

    public function test_does_not_block_when_maintenance_mode_is_off(): void
    {
        delete_option('wpmcp_maintenance');
        wp_set_current_user(0);

        $this->assertFalse((new Maintenance_Guard())->should_block());
    }

    public function test_blocks_a_logged_out_visitor_when_maintenance_mode_is_on(): void
    {
        update_option('wpmcp_maintenance', ['enabled' => true, 'message' => 'Brb.', 'retry_after' => 3600]);
        wp_set_current_user(0);

        $this->assertTrue((new Maintenance_Guard())->should_block());
    }

    public function test_allows_an_administrator_when_maintenance_mode_is_on(): void
    {
        update_option('wpmcp_maintenance', ['enabled' => true, 'message' => 'Brb.', 'retry_after' => 3600]);

        $admin = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin);

        $this->assertFalse((new Maintenance_Guard())->should_block());
    }

    public function test_blocks_a_logged_in_subscriber_when_maintenance_mode_is_on(): void
    {
        update_option('wpmcp_maintenance', ['enabled' => true, 'message' => 'Brb.', 'retry_after' => 3600]);

        $subscriber = self::factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber);

        $this->assertTrue((new Maintenance_Guard())->should_block());
    }
}
