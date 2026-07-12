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
}
