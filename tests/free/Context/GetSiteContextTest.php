<?php

namespace WPMCP\Tests\Free\Context;

use WPMCP\Tools\Context\Get_Site_Context;

class GetSiteContextTest extends \WP_UnitTestCase
{
    public function test_reports_site_identity_and_versions(): void
    {
        $out = (new Get_Site_Context())->handle([]);

        $this->assertSame(get_bloginfo('name'), $out['site']['name']);
        $this->assertSame(home_url(), $out['site']['url']);
        $this->assertSame(get_bloginfo('description'), $out['site']['tagline']);
        $this->assertSame(get_bloginfo('version'), $out['wordpress_version']);
        $this->assertSame(PHP_VERSION, $out['php_version']);
    }

    public function test_reports_active_theme_and_plugin_summary(): void
    {
        $theme = wp_get_theme();

        $out = (new Get_Site_Context())->handle([]);

        $this->assertSame($theme->get('Name'), $out['theme']['name']);
        $this->assertSame($theme->get('Version'), $out['theme']['version']);
        $this->assertSame((bool) $theme->parent(), $out['theme']['is_child']);

        $active_plugins = (array) get_option('active_plugins', []);
        $this->assertSame(count($active_plugins), $out['plugins']['active_count']);
        $this->assertSame($active_plugins, $out['plugins']['active_slugs']);
    }

    public function test_reports_public_post_type_counts_including_a_freshly_created_post(): void
    {
        $before = (new Get_Site_Context())->handle([]);
        $before_count = 0;
        foreach ($before['post_types'] as $row) {
            if ('post' === $row['name']) {
                $before_count = $row['count'];
            }
        }

        self::factory()->post->create(['post_type' => 'post', 'post_status' => 'publish']);

        $out = (new Get_Site_Context())->handle([]);

        $post_row = null;
        foreach ($out['post_types'] as $row) {
            if ('post' === $row['name']) {
                $post_row = $row;
            }
        }

        $this->assertNotNull($post_row, 'Expected the "post" post type to be present');
        $this->assertSame($before_count + 1, $post_row['count']);
    }

    public function test_reports_public_taxonomies(): void
    {
        $out = (new Get_Site_Context())->handle([]);

        $names = array_column($out['taxonomies'], 'name');
        $this->assertContains('category', $names);
        $this->assertContains('post_tag', $names);
    }
}
