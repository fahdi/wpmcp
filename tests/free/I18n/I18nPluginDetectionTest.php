<?php

namespace WPMCP\Tests\Free\I18n;

/**
 * Proves the optional-plugin test harness for multilingual plugins:
 * wpmcp_i18n_plugin() reports which i18n plugin (if any) is active in the
 * current test run, so the i18n tool tests can gate themselves the same way
 * the ACF/WooCommerce/SEO tests do.
 */
class I18nPluginDetectionTest extends \WP_UnitTestCase
{
    public function test_reports_polylang_when_active(): void
    {
        if (! (function_exists('pll_the_languages') || defined('POLYLANG_VERSION'))) {
            $this->markTestSkipped('Polylang is not installed in this test environment.');
        }

        $this->assertSame('polylang', wpmcp_i18n_plugin());
    }

    public function test_reports_wpml_when_active(): void
    {
        if (! defined('ICL_SITEPRESS_VERSION')) {
            $this->markTestSkipped('WPML is not installed in this test environment.');
        }

        $this->assertSame('wpml', wpmcp_i18n_plugin());
    }

    public function test_reports_empty_string_when_no_i18n_plugin_active(): void
    {
        if (function_exists('pll_the_languages') || defined('POLYLANG_VERSION') || defined('ICL_SITEPRESS_VERSION')) {
            $this->markTestSkipped('An i18n plugin is active in this test environment.');
        }

        $this->assertSame('', wpmcp_i18n_plugin());
    }
}
