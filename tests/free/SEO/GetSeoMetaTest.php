<?php

namespace WPMCP\Tests\Free\SEO;

use WPMCP\Tools\SEO\Get_SEO_Meta;
use WPMCP\Tools\SEO\SEO_Adapter;

class GetSeoMetaTest extends \WP_UnitTestCase
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

    public function test_returns_seo_meta_for_a_post(): void
    {
        if ('' === wpmcp_seo_plugin()) {
            $this->markTestSkipped('No SEO plugin active');
        }

        $post_id = $this->post();
        SEO_Adapter::update_meta($post_id, [
            'title'         => 'SEO title',
            'description'   => 'SEO description',
            'focus_keyword' => 'keyword',
            'canonical'     => 'https://example.com/page',
            'noindex'       => true,
            'nofollow'      => false,
        ]);

        $out = (new Get_SEO_Meta())->handle(['post_id' => $post_id]);

        $this->assertSame($post_id, $out['post_id']);
        $this->assertSame('SEO title', $out['title']);
        $this->assertSame('SEO description', $out['description']);
        $this->assertSame('keyword', $out['focus_keyword']);
        $this->assertSame('https://example.com/page', $out['canonical']);
        $this->assertTrue($out['noindex']);
        $this->assertFalse($out['nofollow']);
    }

    public function test_returns_defaults_when_no_seo_meta_set(): void
    {
        if ('' === wpmcp_seo_plugin()) {
            $this->markTestSkipped('No SEO plugin active');
        }

        $post_id = $this->post();

        $out = (new Get_SEO_Meta())->handle(['post_id' => $post_id]);

        $this->assertSame('', $out['title']);
        $this->assertFalse($out['noindex']);
    }

    public function test_requires_post_id(): void
    {
        if ('' === wpmcp_seo_plugin()) {
            $this->markTestSkipped('No SEO plugin active');
        }

        $this->expectException(\InvalidArgumentException::class);
        (new Get_SEO_Meta())->handle([]);
    }
}
