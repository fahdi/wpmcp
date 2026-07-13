<?php

namespace WPMCP\Tests\Free\Backup;

use WPMCP\Tools\Backup\Backup_Job_Store;
use WPMCP\Tools\Backup\Cancel_Backup_Job;
use WPMCP\Tools\Backup\Run_Backup_Job;
use WPMCP\Tools\Backup\Trigger_Backup;

/**
 * cancel-backup-job marks a queued job canceled and unschedules its cron
 * event, so a mistakenly triggered backup never actually runs. A job that
 * is no longer queued (already running, completed, failed, or canceled) is
 * refused with a clear WP_Error rather than silently no-op'd, since
 * "cancel" would be misleading once the job has moved past queued.
 */
class CancelBackupJobTest extends \WP_UnitTestCase
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
        wp_clear_scheduled_hook(Run_Backup_Job::HOOK);
        parent::tearDown();
    }

    public function test_cancels_a_queued_job_and_unschedules_its_cron_event(): void
    {
        $triggered = (new Trigger_Backup())->handle(['type' => 'full', 'scope' => 'all']);
        $job_id    = $triggered['job_id'];

        $this->assertNotFalse(wp_next_scheduled(Run_Backup_Job::HOOK, [$job_id]));

        $out = (new Cancel_Backup_Job())->handle(['job_id' => $job_id]);

        $this->assertSame('canceled', $out['status']);
        $this->assertSame('canceled', Backup_Job_Store::get($job_id)['status']);
        $this->assertFalse(wp_next_scheduled(Run_Backup_Job::HOOK, [$job_id]));
    }

    public function test_refuses_to_cancel_a_completed_job(): void
    {
        $job = Backup_Job_Store::create('full', 'all');
        Backup_Job_Store::update($job['id'], ['status' => 'completed']);

        $out = (new Cancel_Backup_Job())->handle(['job_id' => $job['id']]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('wpmcp_backup_job_not_cancelable', $out->get_error_code());
        $this->assertSame('completed', Backup_Job_Store::get($job['id'])['status']);
    }

    public function test_refuses_to_cancel_a_running_job(): void
    {
        $job = Backup_Job_Store::create('full', 'all');
        Backup_Job_Store::update($job['id'], ['status' => 'running']);

        $out = (new Cancel_Backup_Job())->handle(['job_id' => $job['id']]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('wpmcp_backup_job_not_cancelable', $out->get_error_code());
    }

    public function test_returns_wp_error_for_an_unknown_job_id(): void
    {
        $out = (new Cancel_Backup_Job())->handle(['job_id' => 999999]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('wpmcp_backup_job_not_found', $out->get_error_code());
    }
}
