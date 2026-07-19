<?php

namespace WPMCP\Tests\Free\Integrations;

use WPMCP\Safety\Snapshot_Store;
use WPMCP\Tools\Rollback_Operation;

/**
 * Every dispatched write with a snapshotable target must route through
 * Safe_Mutation: snapshot first, and the resulting operation_id must be
 * restorable through the ordinary rollback-operation tool.
 */
class DispatcherSafetyTest extends \WP_UnitTestCase
{
    private Fixture_Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();
        Snapshot_Store::install();
        Fixture_Integration::reset();
        $this->integration = new Fixture_Integration();
        wp_set_current_user(self::factory()->user->create([ 'role' => 'editor' ]));
    }

    protected function tearDown(): void
    {
        Fixture_Integration::reset();
        parent::tearDown();
    }

    public function test_dispatched_write_snapshots_first_and_is_recoverable(): void
    {
        $post_id = self::factory()->post->create([ 'post_content' => 'ORIGINAL' ]);

        $out = $this->integration->handle_write([
            'operation'  => 'set-content',
            'args'       => [ 'post_id' => $post_id, 'content' => 'MUTATED' ],
            'session_id' => 's-65',
        ]);

        $this->assertArrayNotHasKey('error', $out);
        $this->assertTrue($out['recoverable']);
        $this->assertNotEmpty($out['operation_id']);
        $this->assertSame('MUTATED', get_post($post_id)->post_content);

        $row = Snapshot_Store::get_by_operation($out['operation_id']);
        $this->assertNotNull($row);
        $this->assertSame('ORIGINAL', $row['snapshot']['data']['post']['post_content']);
        $this->assertSame('testint-write', $row['tool']);
    }

    public function test_dispatched_write_is_restorable_via_rollback_operation(): void
    {
        $post_id = self::factory()->post->create([ 'post_content' => 'ORIGINAL' ]);

        $out = $this->integration->handle_write([
            'operation' => 'set-content',
            'args'      => [ 'post_id' => $post_id, 'content' => 'MUTATED' ],
        ]);
        $this->assertSame('MUTATED', get_post($post_id)->post_content);

        (new Rollback_Operation())->handle([ 'operation_id' => $out['operation_id'] ]);

        $this->assertSame('ORIGINAL', get_post($post_id)->post_content);
    }

    public function test_write_without_snapshotable_target_runs_but_is_flagged_unrecoverable(): void
    {
        $before = $this->snapshotCount();

        $out = $this->integration->handle_write([ 'operation' => 'no-target-write' ]);

        $this->assertArrayNotHasKey('error', $out);
        $this->assertFalse($out['recoverable']);
        $this->assertArrayNotHasKey('operation_id', $out);
        $this->assertSame($before, $this->snapshotCount());
    }

    public function test_rejected_write_leaves_no_snapshot_behind(): void
    {
        $before = $this->snapshotCount();

        $this->integration->handle_write([ 'operation' => 'set-content', 'args' => [ 'post_id' => 1 ] ]);

        $this->assertSame($before, $this->snapshotCount());
        $this->assertSame([], Fixture_Integration::$calls);
    }

    private function snapshotCount(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpmcp_snapshots");
    }
}
