<?php

namespace WPMCP\Tests\Free\Content;

use WPMCP\Tools\Content\Delete_Post;
use WPMCP\Safety\{Snapshot_Store, Rollback_Service};

class DeletePostTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Snapshot_Store::install();
    }

    public function test_trashes_by_default(): void
    {
        $id = self::factory()->post->create(['post_status' => 'publish']);

        $out = (new Delete_Post())->handle(['post_id' => $id]);

        $this->assertSame('trashed', $out['deleted']);
        $this->assertSame('trash', get_post($id)->post_status);
    }

    public function test_force_deletes_permanently(): void
    {
        $id = self::factory()->post->create(['post_status' => 'publish']);

        $out = (new Delete_Post())->handle(['post_id' => $id, 'force' => true, 'session_id' => 's1']);

        $this->assertSame('deleted', $out['deleted']);
        $this->assertArrayHasKey('operation_id', $out);
        $this->assertNull(get_post($id));
        $this->assertNotNull(Snapshot_Store::get_by_operation($out['operation_id']));
    }

    public function test_force_delete_rollback_resurrects_the_post(): void
    {
        $id = self::factory()->post->create(['post_title' => 'keep me', 'post_content' => 'body', 'post_status' => 'publish']);

        $out = (new Delete_Post())->handle(['post_id' => $id, 'force' => true, 'session_id' => 's1']);
        $this->assertNull(get_post($id));

        $this->assertTrue(Rollback_Service::restore_operation($out['operation_id']));

        $restored = get_post($id);
        $this->assertNotNull($restored);
        $this->assertSame('keep me', $restored->post_title);
        $this->assertSame('body', $restored->post_content);
    }

    /**
     * Regression test: a force-delete + rollback must restore the FULL post
     * row, not just content/title/status. Before the fix, resurrection went
     * through wp_insert_post() with only those 3 fields, so every other
     * column fell back to wp_insert_post()'s defaults: post_type became
     * 'post' (a force-deleted CPT/page came back as a plain post), and
     * post_author/post_parent/post_name (slug) were lost.
     */
    public function test_force_delete_rollback_restores_type_author_parent_and_slug_for_a_cpt(): void
    {
        register_post_type('wpmcp_test_cpt', ['public' => true, 'supports' => ['title', 'editor']]);

        $author_id = self::factory()->user->create(['role' => 'editor']);
        $parent_id = self::factory()->post->create(['post_type' => 'wpmcp_test_cpt']);

        $id = self::factory()->post->create([
            'post_type'   => 'wpmcp_test_cpt',
            'post_author' => $author_id,
            'post_parent' => $parent_id,
            'post_name'   => 'my-custom-slug',
            'post_title'  => 'CPT post',
            'post_status' => 'publish',
        ]);

        $out = (new Delete_Post())->handle(['post_id' => $id, 'force' => true, 'session_id' => 's1']);
        $this->assertNull(get_post($id));

        $this->assertTrue(Rollback_Service::restore_operation($out['operation_id']));

        $restored = get_post($id);
        $this->assertNotNull($restored);
        $this->assertSame('wpmcp_test_cpt', $restored->post_type);
        $this->assertSame($author_id, (int) $restored->post_author);
        $this->assertSame($parent_id, (int) $restored->post_parent);
        $this->assertSame('my-custom-slug', $restored->post_name);

        unregister_post_type('wpmcp_test_cpt');
    }
}
