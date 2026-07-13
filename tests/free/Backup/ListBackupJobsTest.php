<?php

namespace WPMCP\Tests\Free\Backup;

use WPMCP\Tools\Backup\Backup_Job_Store;
use WPMCP\Tools\Backup\List_Backup_Jobs;

/**
 * list-backup-jobs is a thin read-only wrapper around Backup_Job_Store::list():
 * newest jobs first, with an optional status filter.
 */
class ListBackupJobsTest extends \WP_UnitTestCase
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
        parent::tearDown();
    }

    public function test_lists_jobs_newest_first(): void
    {
        Backup_Job_Store::set_clock_for_tests(1700000000);
        $first  = Backup_Job_Store::create('full', 'all');
        $second = Backup_Job_Store::create('full', 'all');

        $out = (new List_Backup_Jobs())->handle([]);

        $this->assertSame([$second['id'], $first['id']], array_column($out['jobs'], 'id'));
    }

    public function test_filters_by_status(): void
    {
        Backup_Job_Store::set_clock_for_tests(1700000000);
        $queued    = Backup_Job_Store::create('full', 'all');
        $completed = Backup_Job_Store::create('full', 'all');
        Backup_Job_Store::update($completed['id'], ['status' => 'completed']);

        $out = (new List_Backup_Jobs())->handle(['status' => 'completed']);

        $this->assertCount(1, $out['jobs']);
        $this->assertSame($completed['id'], $out['jobs'][0]['id']);
    }

    public function test_returns_an_empty_list_when_there_are_no_jobs(): void
    {
        $out = (new List_Backup_Jobs())->handle([]);

        $this->assertSame([], $out['jobs']);
    }
}
