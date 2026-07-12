<?php

namespace WPMCP\Tests\Free\Cache;

use WPMCP\Tools\Cache\Get_Cache_Status;
use WPMCP\Tools\Cache\Page_Cache_Detector;

class GetCacheStatusTest extends \WP_UnitTestCase
{
    public function test_reports_the_three_cache_layers(): void
    {
        $out = (new Get_Cache_Status())->handle([]);

        $this->assertArrayHasKey('object_cache', $out);
        $this->assertArrayHasKey('opcache', $out);
        $this->assertArrayHasKey('page_cache', $out);
    }

    public function test_object_cache_layer_reflects_ext_object_cache(): void
    {
        $out = (new Get_Cache_Status())->handle([]);

        $this->assertArrayHasKey('persistent', $out['object_cache']);
        // The tool normalizes the WP function's loose return to a strict bool.
        $this->assertSame((bool) wp_using_ext_object_cache(), $out['object_cache']['persistent']);
        $this->assertIsBool($out['object_cache']['persistent']);
    }

    public function test_opcache_layer_reflects_function_availability(): void
    {
        $out = (new Get_Cache_Status())->handle([]);

        $this->assertArrayHasKey('available', $out['opcache']);
        $this->assertSame(function_exists('opcache_get_status'), $out['opcache']['available']);
    }

    public function test_page_cache_reports_no_plugin_when_none_present(): void
    {
        // A detector whose signature map points at symbols that do not exist.
        $detector = new Page_Cache_Detector([
            'WP Rocket' => ['functions' => ['wpmcp_absent_rocket_fn'], 'constants' => []],
        ]);

        $result = $detector->detect();

        $this->assertFalse($result['detected']);
        $this->assertSame([], $result['plugins']);
    }

    public function test_page_cache_detects_a_plugin_by_its_signature_function(): void
    {
        if (! function_exists('wpmcp_stub_rocket_signature')) {
            eval('function wpmcp_stub_rocket_signature() {}');
        }

        $detector = new Page_Cache_Detector([
            'WP Rocket' => ['functions' => ['wpmcp_stub_rocket_signature'], 'constants' => []],
        ]);

        $result = $detector->detect();

        $this->assertTrue($result['detected']);
        $this->assertContains('WP Rocket', $result['plugins']);
    }

    public function test_page_cache_detects_a_plugin_by_its_signature_constant(): void
    {
        if (! defined('WPMCP_STUB_W3TC')) {
            define('WPMCP_STUB_W3TC', true);
        }

        $detector = new Page_Cache_Detector([
            'W3 Total Cache' => ['functions' => [], 'constants' => ['WPMCP_STUB_W3TC']],
        ]);

        $result = $detector->detect();

        $this->assertTrue($result['detected']);
        $this->assertContains('W3 Total Cache', $result['plugins']);
    }

    public function test_status_page_cache_uses_real_signatures_and_reports_none(): void
    {
        // In the test runtime no real page-cache plugin is loaded, so the
        // default detector used by the status tool must report none detected.
        $out = (new Get_Cache_Status())->handle([]);

        $this->assertArrayHasKey('detected', $out['page_cache']);
        $this->assertArrayHasKey('plugins', $out['page_cache']);
        $this->assertFalse($out['page_cache']['detected']);
        $this->assertSame([], $out['page_cache']['plugins']);
    }
}
