<?php

namespace WPMCP\Tests\Free\Cron;

use WPMCP\Tools\Cron\Schedule_Event;
use WPMCP\Tools\Rollback_Operation;
use WPMCP\Safety\Snapshot_Store;

class ScheduleEventTest extends \WP_UnitTestCase
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

    public function test_schedules_a_recurring_event(): void
    {
        $out = (new Schedule_Event())->handle([
            'hook'       => 'wpmcp_cron_test_event',
            'recurrence' => 'hourly',
        ]);

        $this->assertIsInt(wp_next_scheduled('wpmcp_cron_test_event'));
        $this->assertSame('hourly', $out['recurrence']);
        $this->assertTrue($out['recoverable']);
        $this->assertArrayHasKey('operation_id', $out);
    }

    public function test_schedules_a_single_event_when_no_recurrence(): void
    {
        $timestamp = time() + HOUR_IN_SECONDS;

        $out = (new Schedule_Event())->handle([
            'hook'      => 'wpmcp_cron_test_event',
            'timestamp' => $timestamp,
        ]);

        $this->assertSame($timestamp, wp_next_scheduled('wpmcp_cron_test_event'));
        $this->assertNull($out['recurrence']);
    }

    public function test_rejects_an_unknown_recurrence(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Schedule_Event())->handle([
            'hook'       => 'wpmcp_cron_test_event',
            'recurrence' => 'every_picosecond',
        ]);
    }

    public function test_requires_a_hook(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Schedule_Event())->handle(['recurrence' => 'hourly']);
    }

    public function test_refuses_to_schedule_a_protected_core_hook(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Schedule_Event())->handle([
            'hook'       => 'wp_version_check',
            'recurrence' => 'hourly',
        ]);
    }

    public function test_schedule_is_undoable(): void
    {
        $out = (new Schedule_Event())->handle([
            'hook'       => 'wpmcp_cron_test_event',
            'recurrence' => 'hourly',
        ]);

        $this->assertIsInt(wp_next_scheduled('wpmcp_cron_test_event'));

        $rolled_back = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($rolled_back['restored']);

        $this->assertFalse(wp_next_scheduled('wpmcp_cron_test_event'));
    }
}
