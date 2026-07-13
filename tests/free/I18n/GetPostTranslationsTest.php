<?php

namespace WPMCP\Tests\Free\I18n;

use WPMCP\Tools\I18n\Get_Post_Translations;

/**
 * Read tool: returns a post's translations under a 'translations' key.
 *
 * The real Polylang translation lookup cannot run in the unit harness (its
 * API is not booted there), so against the un-booted plugin the map is empty.
 * These tests assert the tool's own contract: the required-arg validation and
 * the shape of the response envelope, which hold regardless of whether any
 * translation is configured.
 */
class GetPostTranslationsTest extends \WP_UnitTestCase
{
    private array $created = [];

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
        (new Get_Post_Translations())->handle([]);
    }

    public function test_returns_post_id_and_translations(): void
    {
        $post_id = $this->post();

        $out = (new Get_Post_Translations())->handle(['post_id' => $post_id]);

        $this->assertSame($post_id, $out['post_id']);
        $this->assertArrayHasKey('translations', $out);
        $this->assertIsArray($out['translations']);
    }
}
