<?php

namespace WPMCP\Tests\Free\Cron;

use WPMCP\Tools\Cron\Unschedule_Event;
use WPMCP\Tools\Rollback_Operation;
use WPMCP\Safety\Snapshot_Store;

class UnscheduleEventTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Snapshot_Store::install();
    }

    protected function tearDown(): void
    {
        wp_clear_scheduled_hook('wpmcp_cron_test_event');
        parent::tearDown();
    }

    public function test_clears_all_events_for_a_hook(): void
    {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'wpmcp_cron_test_event');
        $this->assertIsInt(wp_next_scheduled('wpmcp_cron_test_event'));

        $out = (new Unschedule_Event())->handle(['hook' => 'wpmcp_cron_test_event']);

        $this->assertFalse(wp_next_scheduled('wpmcp_cron_test_event'));
        $this->assertTrue($out['recoverable']);
        $this->assertArrayHasKey('operation_id', $out);
    }

    public function test_unschedules_a_single_timestamped_event(): void
    {
        $timestamp = time() + HOUR_IN_SECONDS;
        wp_schedule_event($timestamp, 'hourly', 'wpmcp_cron_test_event');

        (new Unschedule_Event())->handle([
            'hook'      => 'wpmcp_cron_test_event',
            'timestamp' => $timestamp,
        ]);

        $this->assertFalse(wp_next_scheduled('wpmcp_cron_test_event'));
    }

    public function test_requires_a_hook(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Unschedule_Event())->handle([]);
    }

    public function test_unschedule_is_undoable(): void
    {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'wpmcp_cron_test_event');

        $out = (new Unschedule_Event())->handle(['hook' => 'wpmcp_cron_test_event']);
        $this->assertFalse(wp_next_scheduled('wpmcp_cron_test_event'));

        $rolled_back = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($rolled_back['restored']);

        $this->assertIsInt(wp_next_scheduled('wpmcp_cron_test_event'));
    }
}
