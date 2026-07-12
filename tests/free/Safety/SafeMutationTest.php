<?php

namespace WPMCP\Tests\Free\Safety;

use WPMCP\Safety\{Safe_Mutation, Snapshot_Store, Mutation_Failed, Rollback_Service};

class SafeMutationTest extends \WP_UnitTestCase {
    protected function setUp(): void { parent::setUp(); Snapshot_Store::install(); }
    private function ctx( int $id ): array { return ['object_type'=>'post','object_id'=>$id,'session_id'=>'s1','tool_name'=>'update-blocks','args'=>[]]; }

    public function test_successful_run_snapshots_and_applies(): void {
        $id = self::factory()->post->create( [ 'post_content' => 'OLD' ] );
        $out = Safe_Mutation::run( $this->ctx($id), function () use ($id) {
            wp_update_post( [ 'ID' => $id, 'post_content' => 'NEW' ] );
            return 'ok';
        } );
        $this->assertSame( 'NEW', get_post( $id )->post_content );
        $this->assertNotNull( Snapshot_Store::get_by_operation( $out['operation_id'] ) );
    }

    public function test_verify_failure_rolls_back_and_throws(): void {
        $id = self::factory()->post->create( [ 'post_content' => 'OLD' ] );
        $this->expectException( Mutation_Failed::class );
        try {
            Safe_Mutation::run( $this->ctx($id),
                function () use ($id) { wp_update_post( [ 'ID' => $id, 'post_content' => 'BROKEN' ] ); },
                fn() => false
            );
        } finally {
            $this->assertSame( 'OLD', get_post( $id )->post_content );
        }
    }

    public function test_run_snapshots_and_applies_for_an_option(): void {
        update_option( 'blogname', 'Original Name' );

        $ctx = [ 'object_type' => 'option', 'object_id' => 'blogname', 'session_id' => 's1', 'tool_name' => 'update-settings', 'args' => [] ];
        $out = Safe_Mutation::run( $ctx, function () {
            update_option( 'blogname', 'New Name' );
            return 'ok';
        } );

        $this->assertSame( 'New Name', get_option( 'blogname' ) );

        $row = Snapshot_Store::get_by_operation( $out['operation_id'] );
        $this->assertNotNull( $row );
        $this->assertSame( 'option', $row['snapshot']['object_type'] );
        $this->assertSame( 'blogname', $row['snapshot']['data']['name'] );
        $this->assertSame( 'Original Name', $row['snapshot']['data']['value'] );

        Rollback_Service::apply_snapshot( $row['snapshot'] );
        $this->assertSame( 'Original Name', get_option( 'blogname' ) );
    }
}
