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
}
