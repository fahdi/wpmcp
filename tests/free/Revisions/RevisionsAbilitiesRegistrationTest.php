<?php

namespace WPMCP\Tests\Free\Revisions;

class RevisionsAbilitiesRegistrationTest extends \WP_UnitTestCase
{
    public function test_all_revisions_tools_are_registered_as_free_abilities(): void
    {
        $names = array_keys(wp_get_abilities());

        foreach ([
            'wpmcp/list-revisions',
            'wpmcp/get-revision',
            'wpmcp/restore-revision',
        ] as $name) {
            $this->assertContains($name, $names, "Expected {$name} to be registered");
        }
    }
}
