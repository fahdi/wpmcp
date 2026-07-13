<?php

namespace WPMCP\Tests\Free\Code;

use WPMCP\Tools\Code\Php_Snippet_Guard;

/**
 * Even when PHP snippet execution is enabled (Php_Snippet_Guard::is_enabled()),
 * a production environment refuses to run any snippet unless a SEPARATE,
 * explicit WPMCP_ALLOW_PHP_EXEC_ON_PRODUCTION override is also set.
 *
 * This is stricter than Wp_Cli_Guard::is_allowed_on_environment(): an
 * unknown or empty environment type (wp_get_environment_type() unavailable,
 * misconfigured, or returning '') is treated as production and refused
 * (fail CLOSED), rather than treated as "not production" and allowed. Only
 * the three explicit values 'local', 'development', 'staging' proceed
 * without the production override. RCE has no safe "unknown" default.
 */
class PhpSnippetGuardEnvironmentTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        Php_Snippet_Guard::set_environment_override(null);
        remove_all_filters('wpmcp_allow_php_exec_on_production');
        parent::tearDown();
    }

    public function test_refuses_production_by_default(): void
    {
        Php_Snippet_Guard::set_environment_override('production');
        $this->assertFalse(Php_Snippet_Guard::is_allowed_on_environment());
    }

    public function test_refuses_unknown_environment_value_by_default(): void
    {
        Php_Snippet_Guard::set_environment_override('some-unrecognized-value');
        $this->assertFalse(Php_Snippet_Guard::is_allowed_on_environment());
    }

    public function test_refuses_empty_environment_value_by_default(): void
    {
        Php_Snippet_Guard::set_environment_override('');
        $this->assertFalse(Php_Snippet_Guard::is_allowed_on_environment());
    }

    public function test_allows_production_with_explicit_override(): void
    {
        Php_Snippet_Guard::set_environment_override('production');
        add_filter('wpmcp_allow_php_exec_on_production', '__return_true');

        $this->assertTrue(Php_Snippet_Guard::is_allowed_on_environment());
    }

    public function test_allows_unknown_environment_with_explicit_override(): void
    {
        Php_Snippet_Guard::set_environment_override('totally-unknown');
        add_filter('wpmcp_allow_php_exec_on_production', '__return_true');

        $this->assertTrue(Php_Snippet_Guard::is_allowed_on_environment());
    }

    public function test_allows_development(): void
    {
        Php_Snippet_Guard::set_environment_override('development');
        $this->assertTrue(Php_Snippet_Guard::is_allowed_on_environment());
    }

    public function test_allows_staging(): void
    {
        Php_Snippet_Guard::set_environment_override('staging');
        $this->assertTrue(Php_Snippet_Guard::is_allowed_on_environment());
    }

    public function test_allows_local(): void
    {
        Php_Snippet_Guard::set_environment_override('local');
        $this->assertTrue(Php_Snippet_Guard::is_allowed_on_environment());
    }
}
