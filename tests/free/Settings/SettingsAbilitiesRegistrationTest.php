<?php

namespace WPMCP\Tests\Free\Settings;

class SettingsAbilitiesRegistrationTest extends \WP_UnitTestCase
{
    public function test_all_settings_tools_are_registered_as_free_abilities(): void
    {
        $names = array_keys(wp_get_abilities());

        foreach (['wpmcp/get-settings', 'wpmcp/update-settings'] as $name) {
            $this->assertContains($name, $names, "Expected {$name} to be registered");
        }
    }

    public function test_settings_abilities_have_description_and_category(): void
    {
        $abilities = wp_get_abilities();

        foreach (['wpmcp/get-settings', 'wpmcp/update-settings'] as $name) {
            $ability = $abilities[ $name ];
            $this->assertNotEmpty($ability->get_description(), "Expected {$name} to have a description");
            $this->assertSame('wpmcp', $ability->get_category());
        }
    }
}
