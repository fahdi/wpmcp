<?php

namespace WPMCP\Tests\Free\I18n;

use WPMCP\Tools\I18n\Link_Post_Translations;
use WPMCP\Tools\Rollback_Operation;
use WPMCP\Safety\Snapshot_Store;

/**
 * Write tool: link a set of posts as translations of one another, routed
 * through Safe_Mutation on the primary (first) post so that post's state is
 * snapshotted.
 *
 * Polylang's real translation linking cannot run in the unit harness (its API
 * is not booted there), so the inner mutation is a no-op against the un-booted
 * plugin. What IS exercised for real: the required-arg validation, and the
 * Safe_Mutation envelope on the primary post (operation_id present, snapshot
 * retrievable, rollback-able). The documented limitation, that rollback only
 * restores the primary post and not the other linked posts, is a property of
 * the tool by design and is asserted only to the extent the primary-post
 * snapshot exists.
 */
class LinkPostTranslationsTest extends \WP_UnitTestCase
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

    public function test_requires_translations(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Link_Post_Translations())->handle([]);
    }

    public function test_requires_a_non_empty_translations_list(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Link_Post_Translations())->handle(['translations' => []]);
    }

    public function test_write_snapshots_the_primary_post_and_is_recoverable(): void
    {
        $en = $this->post();
        $fr = $this->post();

        $out = (new Link_Post_Translations())->handle([
            'translations' => [
                ['language' => 'en', 'post_id' => $en],
                ['language' => 'fr', 'post_id' => $fr],
            ],
        ]);

        $this->assertSame($en, $out['primary_post_id']);
        $this->assertArrayHasKey('operation_id', $out);
        $this->assertTrue($out['recoverable']);
        $this->assertNotNull(Snapshot_Store::get_by_operation($out['operation_id']));

        $rolled_back = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($rolled_back['restored']);
    }
}
