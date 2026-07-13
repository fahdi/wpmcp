<?php

namespace WPMCP\Tests\Free\Linking;

use WPMCP\Tools\Linking\Link_Graph;

class LinkGraphTest extends \WP_UnitTestCase
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

    public function test_resolves_permalink_href_to_outgoing_edge(): void
    {
        $b = self::factory()->post->create(['post_title' => 'Target B', 'post_status' => 'publish']);
        $a = self::factory()->post->create([
            'post_title'   => 'Source A',
            'post_status'  => 'publish',
            'post_content' => '<p>See <a href="' . get_permalink($b) . '">Target B</a>.</p>',
        ]);
        $this->post_ids = [$a, $b];

        $graph = Link_Graph::build(['post'], 200);

        $this->assertContains($b, $graph[$a]['outgoing'], 'A should link to B');
        $this->assertSame([], $graph[$b]['outgoing'], 'B links to nobody');
        $this->assertSame(1, $graph[$b]['incoming'], 'B has one incoming link');
        $this->assertSame(0, $graph[$a]['incoming'], 'A has no incoming links');
    }

    public function test_resolves_query_string_p_id_href(): void
    {
        $b = self::factory()->post->create(['post_title' => 'B', 'post_status' => 'publish']);
        $a = self::factory()->post->create([
            'post_title'   => 'A',
            'post_status'  => 'publish',
            'post_content' => '<a href="' . home_url('/?p=' . $b) . '">B</a>',
        ]);
        $this->post_ids = [$a, $b];

        $graph = Link_Graph::build(['post'], 200);

        $this->assertContains($b, $graph[$a]['outgoing']);
    }

    public function test_ignores_external_and_unresolvable_hrefs(): void
    {
        $a = self::factory()->post->create([
            'post_title'   => 'A',
            'post_status'  => 'publish',
            'post_content' => '<a href="https://example.com/other">Ext</a> <a href="#anchor">Anchor</a>',
        ]);
        $this->post_ids = [$a];

        $graph = Link_Graph::build(['post'], 200);

        $this->assertSame([], $graph[$a]['outgoing']);
    }

    public function test_scan_cap_limits_posts_considered(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post_ids[] = self::factory()->post->create(['post_status' => 'publish']);
        }

        $graph = Link_Graph::build(['post'], 3);

        $this->assertCount(3, $graph, 'Only the 3 most recent posts are scanned');
    }

    public function test_only_published_posts_enter_graph(): void
    {
        $draft = self::factory()->post->create(['post_status' => 'draft']);
        $pub   = self::factory()->post->create(['post_status' => 'publish']);
        $this->post_ids = [$draft, $pub];

        $graph = Link_Graph::build(['post'], 200);

        $this->assertArrayHasKey($pub, $graph);
        $this->assertArrayNotHasKey($draft, $graph);
    }
}
