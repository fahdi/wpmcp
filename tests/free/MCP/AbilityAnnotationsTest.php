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

    public function test_read_operation_defaults_to_readonly_idempotent_nondestructive(): void
    {
        $a = new Ability('wpmcp/get-post', 'free', 'Read a post', [], fn() => [], 'edit_posts', 'content', 'read');

        $this->assertTrue($a->read_only_hint);
        $this->assertFalse($a->destructive_hint);
        $this->assertTrue($a->idempotent_hint);
    }

    public function test_delete_operation_defaults_to_destructive_nonidempotent(): void
    {
        $a = new Ability('wpmcp/delete-post', 'free', 'Delete a post', [], fn() => [], 'edit_posts', 'content', 'delete');

        $this->assertFalse($a->read_only_hint);
        $this->assertTrue($a->destructive_hint);
        $this->assertFalse($a->idempotent_hint);
    }

    public function test_create_operation_defaults_to_nonidempotent_nondestructive(): void
    {
        $a = new Ability('wpmcp/create-post', 'free', 'Create a post', [], fn() => [], 'edit_posts', 'content', 'create');

        $this->assertFalse($a->read_only_hint);
        $this->assertFalse($a->destructive_hint);
        $this->assertFalse($a->idempotent_hint);
    }

    public function test_update_operation_defaults_to_idempotent_nondestructive(): void
    {
        $a = new Ability('wpmcp/update-post', 'free', 'Update a post', [], fn() => [], 'edit_posts', 'content', 'update');

        $this->assertFalse($a->read_only_hint);
        $this->assertFalse($a->destructive_hint);
        $this->assertTrue($a->idempotent_hint);
    }

    public function test_hints_can_be_explicitly_overridden(): void
    {
        // update-plugin/update-theme are destructive, irreversible file overwrites,
        // so 'update' operation's usual idempotent default does not apply.
        $a = new Ability(
            'wpmcp/update-plugin',
            'free',
            'Update a plugin',
            [],
            fn() => [],
            'update_plugins',
            'packages',
            'update',
            false,
            true,
            false
        );

        $this->assertFalse($a->read_only_hint);
        $this->assertTrue($a->destructive_hint);
        $this->assertFalse($a->idempotent_hint);
    }
}
