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
}
