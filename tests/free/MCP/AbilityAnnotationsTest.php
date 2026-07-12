<?php

namespace WPMCP\Tests\Free\MCP;

use WPMCP\MCP\Ability;

class AbilityAnnotationsTest extends \WP_UnitTestCase
{
    public function test_ability_defaults_domain_and_operation_when_omitted(): void
    {
        $a = new Ability('wpmcp/get-page', 'free', 'Read a page', [], fn() => []);

        $this->assertSame('content', $a->domain);
        $this->assertSame('read', $a->operation);
    }

    public function test_ability_accepts_explicit_domain_and_operation(): void
    {
        $a = new Ability(
            'wpmcp/delete-post',
            'free',
            'Delete a post',
            [],
            fn() => [],
            'edit_posts',
            'content',
            'delete'
        );

        $this->assertSame('content', $a->domain);
        $this->assertSame('delete', $a->operation);
    }
}
