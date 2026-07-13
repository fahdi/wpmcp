<?php

namespace WPMCP\Tests\Free\Auth;

use WPMCP\Auth\OAuth_Config;

/**
 * OAuth_Config resolves whether the OAuth subsystem is active at all. It must
 * default to OFF (no constant, no filter) so every existing install is
 * completely unaffected until an integrator opts in, matching the plugin's
 * "opt-in behind an enable flag" requirement for issue #43.
 */
class OAuthConfigTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        remove_all_filters('wpmcp_oauth_enabled');
        parent::tearDown();
    }

    public function test_disabled_by_default(): void
    {
        $this->assertFalse(OAuth_Config::is_enabled());
    }

    public function test_filter_can_enable_it(): void
    {
        add_filter('wpmcp_oauth_enabled', '__return_true');

        $this->assertTrue(OAuth_Config::is_enabled());
    }

    public function test_filter_can_explicitly_keep_it_disabled(): void
    {
        add_filter('wpmcp_oauth_enabled', '__return_false');

        $this->assertFalse(OAuth_Config::is_enabled());
    }
}
