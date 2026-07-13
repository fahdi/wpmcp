<?php

namespace WPMCP\Tests\Free\I18n;

use WPMCP\Tools\I18n\Set_Post_Language;
use WPMCP\Tools\Rollback_Operation;
use WPMCP\Safety\Snapshot_Store;

/**
 * Write tool: assign a post to a language, routed through Safe_Mutation on the
 * post so the change is snapshotted and undoable.
 *
 * Polylang's real language assignment cannot run in the unit harness (its API
 * is not booted there), so the inner mutation is a no-op against the un-booted
 * plugin and the post's translation map stays empty. What IS exercised for
 * real here: the required-arg validation, and the Safe_Mutation envelope,
 * which snapshots the post (via object_type 'post') and returns an
 * operation_id whose snapshot is retrievable and rollback-able, independent of
 * whether the inner language write did anything. The real Polylang-configured
 * assignment-and-rollback (a post's language is a term in the 'language'
 * taxonomy, which the post snapshot captures) is documented on the tool but
 * not runnable in this harness, so it is not asserted here.
 */
class SetPostLanguageTest extends \WP_UnitTestCase
{
    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();
        Snapshot_Store::install();
    }

    protected function tearDown(): void
    {
        foreach ($this->created as $id) {
            wp_delete_post($id, true);
        }
        $this->created = [];
        parent::tearDown();
    }

    private function post(): int
    {
        $id = $this->factory()->post->create();
        $this->created[] = $id;
        return $id;
    }

    public function test_requires_post_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Set_Post_Language())->handle(['language' => 'en']);
    }

    public function test_requires_language(): void
    {
        $post_id = $this->post();
        $this->expectException(\InvalidArgumentException::class);
        (new Set_Post_Language())->handle(['post_id' => $post_id]);
    }

    public function test_write_is_snapshotted_and_recoverable(): void
    {
        $post_id = $this->post();

        $out = (new Set_Post_Language())->handle([
            'post_id'  => $post_id,
            'language' => 'en',
        ]);

        $this->assertSame($post_id, $out['post_id']);
        $this->assertArrayHasKey('operation_id', $out);
        $this->assertTrue($out['recoverable']);
        $this->assertNotNull(Snapshot_Store::get_by_operation($out['operation_id']));

        $rolled_back = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($rolled_back['restored']);
    }
}
