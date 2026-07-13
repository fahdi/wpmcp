<?php

namespace WPMCP\Tests\Free\Governance;

use WPMCP\Governance\Governance;
use WPMCP\MCP\Ability;

class GovernanceTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        delete_option(Governance::OPTION);
        parent::tearDown();
    }

    private function ability(string $name = 'wpmcp/get-post', string $domain = 'content', string $operation = 'read'): Ability
    {
        return new Ability($name, 'free', 'desc', [], fn() => [], 'edit_posts', $domain, $operation);
    }

    public function test_ability_is_enabled_by_default_with_no_configuration(): void
    {
        $this->assertTrue(Governance::is_ability_enabled($this->ability()));
    }

    public function test_wpmcp_ability_enabled_filter_can_disable_a_named_ability(): void
    {
        $ability = $this->ability('wpmcp/delete-post');
        add_filter('wpmcp_ability_enabled', function (bool $enabled, string $name) {
            return 'wpmcp/delete-post' === $name ? false : $enabled;
        }, 10, 2);

        $this->assertFalse(Governance::is_ability_enabled($ability));

        remove_all_filters('wpmcp_ability_enabled');
    }

    public function test_wpmcp_ability_enabled_filter_does_not_affect_other_abilities(): void
    {
        $ability = $this->ability('wpmcp/get-post');
        add_filter('wpmcp_ability_enabled', function (bool $enabled, string $name) {
            return 'wpmcp/delete-post' === $name ? false : $enabled;
        }, 10, 2);

        $this->assertTrue(Governance::is_ability_enabled($ability));

        remove_all_filters('wpmcp_ability_enabled');
    }

    public function test_wpmcp_domain_enabled_filter_can_disable_a_whole_domain(): void
    {
        $ability = $this->ability('wpmcp/delete-rows', 'database', 'delete');
        add_filter('wpmcp_domain_enabled', function (bool $enabled, string $domain) {
            return 'database' === $domain ? false : $enabled;
        }, 10, 2);

        $this->assertFalse(Governance::is_ability_enabled($ability));

        remove_all_filters('wpmcp_domain_enabled');
    }

    public function test_wpmcp_domain_enabled_filter_does_not_affect_other_domains(): void
    {
        $ability = $this->ability('wpmcp/get-post', 'content', 'read');
        add_filter('wpmcp_domain_enabled', function (bool $enabled, string $domain) {
            return 'database' === $domain ? false : $enabled;
        }, 10, 2);

        $this->assertTrue(Governance::is_ability_enabled($ability));

        remove_all_filters('wpmcp_domain_enabled');
    }

    public function test_wpmcp_operation_enabled_filter_can_disable_all_deletes(): void
    {
        $ability = $this->ability('wpmcp/delete-post', 'content', 'delete');
        add_filter('wpmcp_operation_enabled', function (bool $enabled, string $operation) {
            return 'delete' === $operation ? false : $enabled;
        }, 10, 2);

        $this->assertFalse(Governance::is_ability_enabled($ability));

        remove_all_filters('wpmcp_operation_enabled');
    }

    public function test_wpmcp_operation_enabled_filter_does_not_affect_reads(): void
    {
        $ability = $this->ability('wpmcp/get-post', 'content', 'read');
        add_filter('wpmcp_operation_enabled', function (bool $enabled, string $operation) {
            return 'delete' === $operation ? false : $enabled;
        }, 10, 2);

        $this->assertTrue(Governance::is_ability_enabled($ability));

        remove_all_filters('wpmcp_operation_enabled');
    }

    public function test_stored_ability_toggle_disables_an_otherwise_enabled_ability(): void
    {
        $ability = $this->ability('wpmcp/delete-post');

        Governance::set_ability_toggle('wpmcp/delete-post', false);

        $this->assertFalse(Governance::is_ability_enabled($ability));
    }

    public function test_stored_domain_toggle_disables_a_whole_domain(): void
    {
        $ability = $this->ability('wpmcp/delete-rows', 'database', 'delete');

        Governance::set_domain_toggle('database', false);

        $this->assertFalse(Governance::is_ability_enabled($ability));
    }

    public function test_stored_domain_toggle_does_not_affect_other_domains(): void
    {
        $ability = $this->ability('wpmcp/get-post', 'content', 'read');

        Governance::set_domain_toggle('database', false);

        $this->assertTrue(Governance::is_ability_enabled($ability));
    }

    public function test_stored_operation_toggle_disables_all_deletes(): void
    {
        $ability = $this->ability('wpmcp/delete-post', 'content', 'delete');

        Governance::set_operation_toggle('delete', false);

        $this->assertFalse(Governance::is_ability_enabled($ability));
    }

    public function test_stored_operation_toggle_does_not_affect_reads(): void
    {
        $ability = $this->ability('wpmcp/get-post', 'content', 'read');

        Governance::set_operation_toggle('delete', false);

        $this->assertTrue(Governance::is_ability_enabled($ability));
    }

    public function test_stored_enable_toggle_is_a_no_op_and_never_overrides_a_domain_disable(): void
    {
        $ability = $this->ability('wpmcp/delete-rows', 'database', 'delete');

        // Explicit per-ability "enabled" cannot force the ability back on
        // once a broader (domain) layer has disabled it.
        Governance::set_ability_toggle('wpmcp/delete-rows', true);
        Governance::set_domain_toggle('database', false);

        $this->assertFalse(Governance::is_ability_enabled($ability));
    }

    public function test_stored_enable_toggle_composes_with_a_simultaneously_disabling_filter(): void
    {
        $ability = $this->ability('wpmcp/delete-post', 'content', 'delete');

        Governance::set_ability_toggle('wpmcp/delete-post', true);
        add_filter('wpmcp_operation_enabled', function (bool $enabled, string $operation) {
            return 'delete' === $operation ? false : $enabled;
        }, 10, 2);

        $this->assertFalse(Governance::is_ability_enabled($ability));

        remove_all_filters('wpmcp_operation_enabled');
    }

    public function test_stored_enable_toggle_keeps_an_otherwise_enabled_ability_enabled(): void
    {
        $ability = $this->ability('wpmcp/get-post');

        Governance::set_ability_toggle('wpmcp/get-post', true);

        $this->assertTrue(Governance::is_ability_enabled($ability));
    }
}
