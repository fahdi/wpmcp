<?php

namespace WPMCP\Tests\Free\Governance;

use WPMCP\Governance\Governance;
use WPMCP\Identity\Identity_Context;
use WPMCP\Identity\Identity_Store;
use WPMCP\MCP\Ability;

/**
 * Identity scope is an additional narrowing layer on top of capability and
 * Governance: it can only take an otherwise-allowed ability away, never
 * grant one back. See Registrar::register()'s permission_callback for the
 * enforcement point, and Governance::is_within_identity_scope() for the
 * exact allowlist-matching semantics.
 */
class IdentityScopeTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        delete_option(Identity_Store::OPTION);
    }

    protected function tearDown(): void
    {
        Identity_Context::set_current_for_tests(null);
        delete_option(Identity_Store::OPTION);
        parent::tearDown();
    }

    private function ability(string $name = 'wpmcp/get-post', string $domain = 'content', string $operation = 'read'): Ability
    {
        return new Ability($name, 'free', 'desc', [], fn() => [], 'edit_posts', $domain, $operation);
    }

    public function test_no_active_identity_means_no_additional_restriction(): void
    {
        $this->assertTrue(Governance::is_within_identity_scope($this->ability()));
    }

    public function test_an_unknown_identity_name_results_in_default_deny(): void
    {
        Identity_Context::set_current_for_tests('never-registered');

        $this->assertFalse(Governance::is_within_identity_scope($this->ability()));
    }
}
