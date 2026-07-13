<?php

namespace WPMCP\Tests\Free\Code;

use WPMCP\Tools\Code\Php_Snippet_Guard;

/**
 * PHP snippet execution (issue #45) is off unless explicitly opted into:
 * neither the WPMCP_ALLOW_PHP_EXEC constant nor the wpmcp_allow_php_exec
 * filter is set by default, so a fresh install can never eval anything.
 * Either seam being truthy is sufficient to enable it, mirroring
 * Wp_Cli_Guard::is_enabled() and OAuth_Config::is_enabled().
 */
class PhpSnippetGuardEnabledTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        remove_all_filters('wpmcp_allow_php_exec');
        parent::tearDown();
    }

    public function test_disabled_by_default(): void
    {
        $this->assertFalse(Php_Snippet_Guard::is_enabled());
    }

    public function test_enabled_via_filter(): void
    {
        add_filter('wpmcp_allow_php_exec', '__return_true');
        $this->assertTrue(Php_Snippet_Guard::is_enabled());
    }

    public function test_filter_can_force_disable_even_if_constant_were_set(): void
    {
        add_filter('wpmcp_allow_php_exec', '__return_false');
        $this->assertFalse(Php_Snippet_Guard::is_enabled());
    }
}
