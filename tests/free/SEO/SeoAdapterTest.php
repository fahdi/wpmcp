<?php

namespace WPMCP\Tests\Free\SEO;

use WPMCP\Tools\SEO\SEO_Adapter;

/**
 * Proves the adapter's plugin detection: which SEO plugin (if any) is active,
 * reported the same way regardless of whether Yoast or RankMath is running.
 * Gated on wpmcp_seo_plugin() so it skips cleanly when neither is installed.
 */
class SeoAdapterTest extends \WP_UnitTestCase
{
    public function test_detects_the_active_plugin(): void
    {
        $active = wpmcp_seo_plugin();
        if ('' === $active) {
            $this->markTestSkipped('No SEO plugin active');
        }

        $this->assertSame($active, SEO_Adapter::active_plugin());
    }

    public function test_reports_no_plugin_when_neither_is_active(): void
    {
        if ('' !== wpmcp_seo_plugin()) {
            $this->markTestSkipped('An SEO plugin is active in this test environment.');
        }

        $this->assertSame('', SEO_Adapter::active_plugin());
    }

    public function test_update_meta_round_trips_through_get_meta(): void
    {
        if ('' === wpmcp_seo_plugin()) {
            $this->markTestSkipped('No SEO plugin active');
        }

        $post_id = $this->factory()->post->create();

        SEO_Adapter::update_meta($post_id, [
            'title'         => 'A test title',
            'description'   => 'A test description',
            'focus_keyword' => 'test keyword',
            'canonical'     => 'https://example.com/canonical',
            'noindex'       => true,
            'nofollow'      => true,
        ]);

        $meta = SEO_Adapter::get_meta($post_id);

        $this->assertSame('A test title', $meta['title']);
        $this->assertSame('A test description', $meta['description']);
        $this->assertSame('test keyword', $meta['focus_keyword']);
        $this->assertSame('https://example.com/canonical', $meta['canonical']);
        $this->assertTrue($meta['noindex']);
        $this->assertTrue($meta['nofollow']);

        wp_delete_post($post_id, true);
    }

    public function test_update_meta_only_touches_given_fields(): void
    {
        if ('' === wpmcp_seo_plugin()) {
            $this->markTestSkipped('No SEO plugin active');
        }

        $post_id = $this->factory()->post->create();

        SEO_Adapter::update_meta($post_id, ['title' => 'Original title', 'noindex' => true]);
        SEO_Adapter::update_meta($post_id, ['title' => 'Updated title']);

        $meta = SEO_Adapter::get_meta($post_id);

        $this->assertSame('Updated title', $meta['title']);
        $this->assertTrue($meta['noindex']);

        wp_delete_post($post_id, true);
    }

    public function test_get_meta_defaults_when_nothing_set(): void
    {
        if ('' === wpmcp_seo_plugin()) {
            $this->markTestSkipped('No SEO plugin active');
        }

        $post_id = $this->factory()->post->create();

        $meta = SEO_Adapter::get_meta($post_id);

        $this->assertSame('', $meta['title']);
        $this->assertSame('', $meta['description']);
        $this->assertFalse($meta['noindex']);
        $this->assertFalse($meta['nofollow']);

        wp_delete_post($post_id, true);
    }
}
