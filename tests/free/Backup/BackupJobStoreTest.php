<?php

namespace WPMCP\Tests\Free\Backup;

use WPMCP\Tools\Backup\Backup_Job_Store;

/**
 * Backup_Job_Store is a CRUD layer over a single wpmcp_backup_jobs option
 * (an array of job records keyed by job id, plus a 'next_id' sequence used
 * to mint new ids). Job ids are a deterministic incrementing integer
 * sequence, never wp_generate_uuid4()/random, so tests can assert on exact
 * ids. Timestamps come from an injectable clock (set_clock_for_tests), not
 * time(), for the same determinism reason.
 */
class BackupJobStoreTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        delete_option('wpmcp_backup_jobs');
        Backup_Job_Store::set_clock_for_tests(null);
    }

    protected function tearDown(): void
    {
        Backup_Job_Store::set_clock_for_tests(null);
        delete_option('wpmcp_backup_jobs');
        parent::tearDown();
    }

    public function test_create_returns_a_queued_job_with_a_deterministic_id(): void
    {
        Backup_Job_Store::set_clock_for_tests(1700000000);

        $job = Backup_Job_Store::create('full', 'all');

        $this->assertSame(1, $job['id']);
        $this->assertSame('full', $job['type']);
        $this->assertSame('all', $job['scope']);
        $this->assertSame('queued', $job['status']);
        $this->assertSame(1700000000, $job['created_at']);
        $this->assertSame(1700000000, $job['updated_at']);
        $this->assertNull($job['result']);
        $this->assertNull($job['error']);
    }

    public function test_ids_increment_deterministically_across_creates(): void
    {
        Backup_Job_Store::set_clock_for_tests(1700000000);

        $first  = Backup_Job_Store::create('full', 'all');
        $second = Backup_Job_Store::create('full', 'all');

        $this->assertSame(1, $first['id']);
        $this->assertSame(2, $second['id']);
    }

    public function test_get_returns_the_stored_job(): void
    {
        Backup_Job_Store::set_clock_for_tests(1700000000);
        $created = Backup_Job_Store::create('full', 'all');

        $fetched = Backup_Job_Store::get($created['id']);

        $this->assertSame($created, $fetched);
    }

    public function test_get_returns_null_for_an_unknown_job_id(): void
    {
        $this->assertNull(Backup_Job_Store::get(999));
    }

    public function test_list_returns_jobs_newest_first(): void
    {
        Backup_Job_Store::set_clock_for_tests(1700000000);
        $first  = Backup_Job_Store::create('full', 'all');
        $second = Backup_Job_Store::create('full', 'all');
        $third  = Backup_Job_Store::create('full', 'all');

        $ids = array_column(Backup_Job_Store::list(), 'id');

        $this->assertSame([$third['id'], $second['id'], $first['id']], $ids);
    }

    public function test_list_filters_by_status(): void
    {
        Backup_Job_Store::set_clock_for_tests(1700000000);
        $queued = Backup_Job_Store::create('full', 'all');
        $other  = Backup_Job_Store::create('full', 'all');
        Backup_Job_Store::update($other['id'], ['status' => 'completed']);

        $filtered = Backup_Job_Store::list('completed');

        $this->assertCount(1, $filtered);
        $this->assertSame($other['id'], $filtered[0]['id']);
        $this->assertSame('completed', $filtered[0]['status']);
    }

    public function test_update_merges_fields_and_bumps_updated_at(): void
    {
        Backup_Job_Store::set_clock_for_tests(1700000000);
        $job = Backup_Job_Store::create('full', 'all');

        Backup_Job_Store::set_clock_for_tests(1700000500);
        $updated = Backup_Job_Store::update($job['id'], [
            'status' => 'completed',
            'result' => ['file' => '/path/to/backup.xml'],
        ]);

        $this->assertSame('completed', $updated['status']);
        $this->assertSame(['file' => '/path/to/backup.xml'], $updated['result']);
        $this->assertSame(1700000000, $updated['created_at']);
        $this->assertSame(1700000500, $updated['updated_at']);

        $this->assertSame($updated, Backup_Job_Store::get($job['id']));
    }

    public function test_update_returns_null_for_an_unknown_job_id(): void
    {
        $this->assertNull(Backup_Job_Store::update(999, ['status' => 'completed']));
    }
}
