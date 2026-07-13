<?php

namespace WPMCP\Tests\Free\Linking;

use WPMCP\Tools\Linking\Find_Orphan_Posts;

class FindOrphanPostsTest extends \WP_UnitTestCase
{
    /** @var int[] */
    private array $post_ids = [];

    public function tearDown(): void
    {
        foreach ($this->post_ids as $id) {
            wp_delete_post($id, true);
        }
        $this->post_ids = [];
        parent::tearDown();
    }

    public function test_returns_posts_with_zero_incoming_links(): void
    {
        $b = self::factory()->post->create(['post_title' => 'B', 'post_status' => 'publish']);
        $a = self::factory()->post->create([
            'post_title'   => 'A',
            'post_status'  => 'publish',
            'post_content' => '<a href="' . get_permalink($b) . '">B</a>',
        ]);
        $c = self::factory()->post->create(['post_title' => 'C', 'post_status' => 'publish']);
        $this->post_ids = [$a, $b, $c];

        $out = (new Find_Orphan_Posts())->handle(['post_type' => 'post']);

        $orphan_ids = array_column($out['orphans'], 'id');
        $this->assertContains($c, $orphan_ids, 'C links to nobody and nobody links to C');
        $this->assertContains($a, $orphan_ids, 'A has no incoming links');
        $this->assertNotContains($b, $orphan_ids, 'B is linked from A');
    }

    public function test_cap_limits_number_of_orphans_returned(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->post_ids[] = self::factory()->post->create(['post_status' => 'publish']);
        }

        $out = (new Find_Orphan_Posts())->handle(['post_type' => 'post', 'cap' => 2]);

        $this->assertCount(2, $out['orphans']);
        $this->assertSame(4, $out['orphan_total']);
    }

    public function test_post_type_filter_excludes_other_types(): void
    {
        $post = self::factory()->post->create(['post_status' => 'publish', 'post_type' => 'post']);
        $page = self::factory()->post->create(['post_status' => 'publish', 'post_type' => 'page']);
        $this->post_ids = [$post, $page];

        $out = (new Find_Orphan_Posts())->handle(['post_type' => 'page']);

        $ids = array_column($out['orphans'], 'id');
        $this->assertContains($page, $ids);
        $this->assertNotContains($post, $ids);
    }
}
