<?php

namespace WPMCP\Tests\Free\Cache;

use WPMCP\Tools\Cache\Clear_Cache;
use WPMCP\Tools\Cache\Page_Cache_Detector;

class ClearCacheTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['wpmcp_cache_test_cleared']);
        delete_transient('wpmcp_cache_test_transient');
        parent::tearDown();
    }

    public function test_returns_a_per_layer_summary(): void
    {
        $out = (new Clear_Cache())->handle([]);

        $this->assertArrayHasKey('object_cache', $out);
        $this->assertArrayHasKey('transients', $out);
        $this->assertArrayHasKey('opcache', $out);
        $this->assertArrayHasKey('page_cache', $out);
    }

    public function test_flushes_the_object_cache(): void
    {
        wp_cache_set('wpmcp_cache_probe', 'value', 'wpmcp');
        $this->assertSame('value', wp_cache_get('wpmcp_cache_probe', 'wpmcp'));

        $out = (new Clear_Cache())->handle([]);

        $this->assertFalse(wp_cache_get('wpmcp_cache_probe', 'wpmcp'));
        $this->assertTrue($out['object_cache']['flushed']);
    }

    public function test_deletes_a_seeded_transient(): void
    {
        set_transient('wpmcp_cache_test_transient', 'x', HOUR_IN_SECONDS);
        $this->assertSame('x', get_transient('wpmcp_cache_test_transient'));

        $out = (new Clear_Cache())->handle([]);

        $this->assertFalse(get_transient('wpmcp_cache_test_transient'));
        $this->assertGreaterThanOrEqual(1, $out['transients']['deleted']);
    }

    public function test_reports_opcache_layer(): void
    {
        $out = (new Clear_Cache())->handle([]);

        $this->assertArrayHasKey('reset', $out['opcache']);
        $this->assertArrayHasKey('available', $out['opcache']);
        $this->assertSame(function_exists('opcache_reset'), $out['opcache']['available']);
    }

    public function test_invokes_a_detected_page_cache_plugin_clear_api(): void
    {
        if (! function_exists('wpmcp_stub_page_cache_clear')) {
            eval('function wpmcp_stub_page_cache_clear() { $GLOBALS["wpmcp_cache_test_cleared"] = true; }');
        }

        $detector = new Page_Cache_Detector([
            'Stub Cache' => [
                'functions' => ['wpmcp_stub_page_cache_clear'],
                'constants' => [],
                'clear'     => 'wpmcp_stub_page_cache_clear',
            ],
        ]);

        $out = (new Clear_Cache($detector))->handle([]);

        $this->assertTrue($GLOBALS['wpmcp_cache_test_cleared'] ?? false);
        $this->assertSame('cleared', $out['page_cache']['plugins']['Stub Cache']);
    }

    public function test_does_not_fatal_when_no_page_cache_plugin_is_present(): void
    {
        $detector = new Page_Cache_Detector([
            'Absent Cache' => ['functions' => ['wpmcp_absent_clear_fn'], 'constants' => []],
        ]);

        $out = (new Clear_Cache($detector))->handle([]);

        $this->assertSame([], $out['page_cache']['plugins']);
        $this->assertFalse($out['page_cache']['detected']);
    }
}
