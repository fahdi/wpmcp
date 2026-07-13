<?php

namespace WPMCP\Tests\Free\Analytics;

use WPMCP\Tools\Analytics\Analytics_Adapter;

/**
 * Analytics_Adapter maps a neutral analytics/Search Console field set onto
 * whichever provider is active: Google Site Kit, or an explicitly configured
 * set of credentials stored in the 'wpmcp_analytics_config' option.
 *
 * Neither provider can be exercised end to end in this harness: Google Site
 * Kit is not installed here (it is not one of the plugins bundled into this
 * test environment, unlike, say, Polylang), and even a configured-credentials
 * setup would still need a real outbound call to Google's GA4/Search Console
 * APIs to return real data, which this harness has no network access or
 * credentials for. That mirrors how MultisiteAdapterTest and I18nAdapterTest
 * treat their own live-network / live-plugin paths as production-only.
 *
 * What IS testable here: the pure detection logic (active_provider(),
 * connection_status()) against option-based configuration and a filter-based
 * seam for simulating Site Kit's presence, plus the pure normalization
 * helpers against hand-built fixtures (covered in separate test files).
 *
 * Site Kit detection seam: rather than defining a real GOOGLESITEKIT_VERSION
 * constant or declaring a throwaway class in the Google\Site_Kit namespace
 * (either of which could leak into other tests since PHP constants/classes
 * cannot be undefined once declared), active_provider() runs its Site Kit
 * check through
 * apply_filters('wpmcp_analytics_site_kit_active', defined(...) || class_exists(...)).
 * A test can flip this with add_filter() and must remove_filter() in
 * tearDown(), keeping the simulation fully isolated per test.
 */
class AnalyticsAdapterTest extends \WP_UnitTestCase
{
    public function tearDown(): void
    {
        delete_option('wpmcp_analytics_config');
        remove_all_filters('wpmcp_analytics_site_kit_active');
        parent::tearDown();
    }

    public function test_active_provider_is_none_when_nothing_is_configured(): void
    {
        $this->assertSame('', Analytics_Adapter::active_provider());
    }

    public function test_active_provider_is_configured_when_valid_option_is_set(): void
    {
        update_option('wpmcp_analytics_config', [
            'property_id' => '123456789',
            'site_url'    => 'https://example.org',
        ]);

        $this->assertSame('configured', Analytics_Adapter::active_provider());
    }

    public function test_active_provider_is_none_when_configured_option_is_missing_required_keys(): void
    {
        update_option('wpmcp_analytics_config', [
            'property_id' => '',
        ]);

        $this->assertSame('', Analytics_Adapter::active_provider());
    }

    public function test_active_provider_is_site_kit_when_the_detection_filter_reports_active(): void
    {
        add_filter('wpmcp_analytics_site_kit_active', '__return_true');

        $this->assertSame('site-kit', Analytics_Adapter::active_provider());
    }

    public function test_active_provider_prefers_site_kit_over_configured(): void
    {
        update_option('wpmcp_analytics_config', [
            'property_id' => '123456789',
            'site_url'    => 'https://example.org',
        ]);
        add_filter('wpmcp_analytics_site_kit_active', '__return_true');

        $this->assertSame('site-kit', Analytics_Adapter::active_provider());
    }

    public function test_connection_status_reports_none_when_nothing_is_configured(): void
    {
        $status = Analytics_Adapter::connection_status();

        $this->assertSame('none', $status['provider']);
        $this->assertFalse($status['connected']);
        $this->assertIsString($status['detail']);
    }

    public function test_connection_status_reports_configured_and_connected_when_option_is_valid(): void
    {
        update_option('wpmcp_analytics_config', [
            'property_id' => '123456789',
            'site_url'    => 'https://example.org',
        ]);

        $status = Analytics_Adapter::connection_status();

        $this->assertSame('configured', $status['provider']);
        $this->assertTrue($status['connected']);
    }
}
