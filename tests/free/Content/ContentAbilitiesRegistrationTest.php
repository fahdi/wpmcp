<?php

namespace WPMCP\Tests\Free\Content;

class ContentAbilitiesRegistrationTest extends \WP_UnitTestCase
{
    public function test_all_content_tools_are_registered_as_free_abilities(): void
    {
        $names = array_keys(wp_get_abilities());

        foreach ([
            'wpmcp/list-post-types',
            'wpmcp/list-taxonomies',
            'wpmcp/create-post',
            'wpmcp/get-post',
            'wpmcp/update-post',
            'wpmcp/delete-post',
            'wpmcp/list-posts',
            'wpmcp/set-post-terms',
        ] as $name) {
            $this->assertContains($name, $names, "Expected {$name} to be registered");
        }
    }
}
