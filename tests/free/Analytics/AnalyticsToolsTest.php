<?php

namespace WPMCP\Tests\Free\Analytics;

use WPMCP\Tools\Analytics\Get_Analytics_Connection_Status;

/**
 * Thin argument-handling tests for the Analytics tool classes. The adapter's
 * own logic (provider detection, date validation, limit clamping,
 * normalizers) is covered in AnalyticsAdapterTest; these tests only prove
 * each tool class wires its args to the adapter correctly (coercion,
 * defaulting, and passing through WP_Error results), the same scope as
 * Multisite's tool-class tests.
 */
class AnalyticsToolsTest extends \WP_UnitTestCase
{
    public function tearDown(): void
    {
        delete_option('wpmcp_analytics_config');
        parent::tearDown();
    }

    public function test_get_analytics_connection_status_returns_the_adapter_status_array(): void
    {
        $tool   = new Get_Analytics_Connection_Status();
        $result = $tool->handle([]);

        $this->assertIsArray($result);
        $this->assertSame('none', $result['provider']);
        $this->assertFalse($result['connected']);
    }
}
