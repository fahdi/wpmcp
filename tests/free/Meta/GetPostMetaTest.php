<?php

namespace WPMCP\Tests\Free\Meta;

use WPMCP\Tools\Meta\Get_Post_Meta;

class GetPostMetaTest extends \WP_UnitTestCase
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

    public function test_returns_all_meta_for_a_post(): void
    {
        $post_id = $this->post();
        update_post_meta($post_id, 'color', 'blue');
        update_post_meta($post_id, 'size', 'large');

        $out = (new Get_Post_Meta())->handle(['post_id' => $post_id]);

        $this->assertSame($post_id, $out['post_id']);
        $this->assertSame('blue', $out['meta']['color']);
        $this->assertSame('large', $out['meta']['size']);
    }

    public function test_returns_a_single_key_when_requested(): void
    {
        $post_id = $this->post();
        update_post_meta($post_id, 'color', 'blue');
        update_post_meta($post_id, 'size', 'large');

        $out = (new Get_Post_Meta())->handle(['post_id' => $post_id, 'key' => 'color']);

        $this->assertSame(['color' => 'blue'], $out['meta']);
    }

    public function test_requires_post_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Post_Meta())->handle([]);
    }

    public function test_requires_an_existing_post(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Post_Meta())->handle(['post_id' => 999999]);
    }
}
