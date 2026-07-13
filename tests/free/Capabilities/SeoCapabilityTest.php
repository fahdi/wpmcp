<?php

namespace WPMCP\Tests\Free\Capabilities;

use WPMCP\Plugin;

/**
 * Capability gating for the SEO domain: get-seo-status, get-seo-meta, and
 * update-seo-meta all require edit_posts. get-seo-status is always
 * registered; the meta tools are gated behind an active SEO plugin, matching
 * SeoAbilitiesRegistrationTest's guard.
 */
class SeoCapabilityTest extends \WP_UnitTestCase
{
    public static function wpSetUpBeforeClass(): void
    {
        if (0 === did_action('wp_abilities_api_init')) {
            do_action('wp_abilities_api_init');
        }
    }

    protected function tearDown(): void
    {
        wp_set_current_user(0);
        parent::tearDown();
    }

    public function test_get_seo_status_requires_edit_posts(): void
    {
        $abilities = [];
        foreach (Plugin::instance()->registrar()->all() as $ability) {
            $abilities[ $ability->name ] = $ability;
        }

        $this->assertArrayHasKey('wpmcp/get-seo-status', $abilities);
        $this->assertSame('edit_posts', $abilities['wpmcp/get-seo-status']->capability);
    }

    public function test_get_seo_status_denies_subscriber_and_allows_edit_posts(): void
    {
        $abilities = wp_get_abilities();

        $subscriber = self::factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber);
        $this->assertFalse(
            $abilities['wpmcp/get-seo-status']->check_permissions(),
            'wpmcp/get-seo-status must deny a subscriber'
        );

        $author = self::factory()->user->create(['role' => 'author']);
        wp_set_current_user($author);
        $this->assertTrue(
            $abilities['wpmcp/get-seo-status']->check_permissions(),
            'wpmcp/get-seo-status must allow a user holding edit_posts'
        );
    }

    public function test_meta_tools_require_edit_posts_when_an_seo_plugin_is_active(): void
    {
        if ('' === wpmcp_seo_plugin()) {
            $this->markTestSkipped('No SEO plugin active');
        }

        $abilities = [];
        foreach (Plugin::instance()->registrar()->all() as $ability) {
            $abilities[ $ability->name ] = $ability;
        }

        $this->assertSame('edit_posts', $abilities['wpmcp/get-seo-meta']->capability);
        $this->assertSame('edit_posts', $abilities['wpmcp/update-seo-meta']->capability);

        $wp_abilities = wp_get_abilities();

        $subscriber = self::factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber);
        $this->assertFalse($wp_abilities['wpmcp/update-seo-meta']->check_permissions());

        $author = self::factory()->user->create(['role' => 'author']);
        wp_set_current_user($author);
        $this->assertTrue($wp_abilities['wpmcp/update-seo-meta']->check_permissions());
    }
}
