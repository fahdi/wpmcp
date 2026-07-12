<?php

namespace WPMCP\Tests\Free\Media;

class MediaAbilitiesRegistrationTest extends \WP_UnitTestCase
{
    public function test_all_media_tools_are_registered_as_free_abilities(): void
    {
        $names = array_keys(wp_get_abilities());

        foreach ([
            'wpmcp/get-media',
            'wpmcp/update-media',
            'wpmcp/delete-media',
            'wpmcp/sideload-image',
        ] as $name) {
            $this->assertContains($name, $names, "Expected {$name} to be registered");
        }
    }

    public function test_media_abilities_have_description_and_category(): void
    {
        $abilities = wp_get_abilities();

        foreach ([
            'wpmcp/get-media',
            'wpmcp/update-media',
            'wpmcp/delete-media',
            'wpmcp/sideload-image',
        ] as $name) {
            $ability = $abilities[ $name ];
            $this->assertNotEmpty($ability->get_description(), "Expected {$name} to have a description");
            $this->assertSame('wpmcp', $ability->get_category());
        }
    }
}
