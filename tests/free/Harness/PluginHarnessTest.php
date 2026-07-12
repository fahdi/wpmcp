<?php

namespace WPMCP\Tests\Free\Harness;

/**
 * Proves the optional-plugin test harness: when a plugin is activated the suite
 * can see its classes; when it is absent the test skips rather than fails.
 */
class PluginHarnessTest extends \WP_UnitTestCase {
	public function test_elementor_is_available_when_active(): void {
		if ( ! wpmcp_elementor_active() ) {
			$this->markTestSkipped( 'Elementor is not installed in this test environment.' );
		}

		$this->assertTrue( class_exists( '\\Elementor\\Plugin' ) );
	}

	public function test_woocommerce_is_available_when_active(): void {
		if ( ! wpmcp_woocommerce_active() ) {
			$this->markTestSkipped( 'WooCommerce is not installed in this test environment.' );
		}

		$this->assertTrue( class_exists( 'WooCommerce' ) );
		$this->assertTrue( function_exists( 'WC' ) );
	}
}
