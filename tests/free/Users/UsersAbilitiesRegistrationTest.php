<?php

namespace WPMCP\Tests\Free\Users;

class UsersAbilitiesRegistrationTest extends \WP_UnitTestCase
{
    public function test_all_user_tools_are_registered_as_free_abilities(): void
    {
        $names = array_keys(wp_get_abilities());

        foreach (['wpmcp/list-users', 'wpmcp/get-user', 'wpmcp/create-user', 'wpmcp/update-user'] as $name) {
            $this->assertContains($name, $names, "Expected {$name} to be registered");
        }
    }

    public function test_user_abilities_have_description_and_category(): void
    {
        $abilities = wp_get_abilities();

        foreach (['wpmcp/list-users', 'wpmcp/get-user', 'wpmcp/create-user', 'wpmcp/update-user'] as $name) {
            $ability = $abilities[ $name ];
            $this->assertNotEmpty($ability->get_description(), "Expected {$name} to have a description");
            $this->assertSame('wpmcp', $ability->get_category());
        }
    }
}
