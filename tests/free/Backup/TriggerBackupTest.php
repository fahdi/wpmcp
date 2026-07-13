<?php

namespace WPMCP\Tests\Free\Backup;

use WPMCP\Tools\Backup\Backup_Job_Store;
use WPMCP\Tools\Backup\Trigger_Backup;

/**
 * trigger-backup creates a queued job record and schedules a single
 * WP-Cron event (Run_Backup_Job::HOOK) that will execute it, so a backup on
 * a large site can run outside the request/response cycle instead of
 * blocking (and possibly timing out) the calling MCP request.
 */
class TriggerBackupTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        delete_option(Backup_Job_Store::OPTION);
        Backup_Job_Store::set_clock_for_tests(null);
    }

    protected function tearDown(): void
    {
        Backup_Job_Store::set_clock_for_tests(null);
        delete_option(Backup_Job_Store::OPTION);
        wp_clear_scheduled_hook('wpmcp_run_backup_job');
        parent::tearDown();
    }

    public function test_creates_a_queued_job_and_returns_its_id(): void
    {
        Backup_Job_Store::set_clock_for_tests(1700000000);

        $out = (new Trigger_Backup())->handle(['type' => 'full', 'scope' => 'all']);

        $this->assertArrayHasKey('job_id', $out);
        $this->assertSame('queued', $out['status']);

        $job = Backup_Job_Store::get($out['job_id']);
        $this->assertNotNull($job);
        $this->assertSame('queued', $job['status']);
        $this->assertSame('full', $job['type']);
        $this->assertSame('all', $job['scope']);
    }

    public function test_schedules_a_cron_event_for_the_job(): void
    {
        $out = (new Trigger_Backup())->handle(['type' => 'full', 'scope' => 'all']);

        $next = wp_next_scheduled('wpmcp_run_backup_job', [$out['job_id']]);

        $this->assertNotFalse($next);
    }

    public function test_defaults_type_and_scope_when_omitted(): void
    {
        $out = (new Trigger_Backup())->handle([]);

        $job = Backup_Job_Store::get($out['job_id']);
        $this->assertSame('full', $job['type']);
        $this->assertSame('all', $job['scope']);
    }
}
