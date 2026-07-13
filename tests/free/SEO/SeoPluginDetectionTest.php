<?php

namespace WPMCP\Tests\Free\SEO;

/**
 * Proves the optional-plugin test harness for SEO plugins: wpmcp_seo_plugin()
 * reports which SEO plugin (if any) is active in the current test run, so
 * SEO tool tests can gate themselves the same way ACF/WooCommerce tests do.
 */
class SeoPluginDetectionTest extends \WP_UnitTestCase
{
    public function test_reports_yoast_when_active(): void
    {
        if (! (defined('WPSEO_VERSION') || class_exists('WPSEO_Options'))) {
            $this->markTestSkipped('Yoast SEO is not installed in this test environment.');
        }

        $this->assertSame('yoast', wpmcp_seo_plugin());
    }

    public function test_reports_rankmath_when_active(): void
    {
        if (! class_exists('RankMath')) {
            $this->markTestSkipped('RankMath is not installed in this test environment.');
        }

        $this->assertSame('rankmath', wpmcp_seo_plugin());
    }

    public function test_reports_empty_string_when_no_seo_plugin_active(): void
    {
        if (defined('WPSEO_VERSION') || class_exists('WPSEO_Options') || class_exists('RankMath')) {
            $this->markTestSkipped('An SEO plugin is active in this test environment.');
        }

        $this->assertSame('', wpmcp_seo_plugin());
    }
}
