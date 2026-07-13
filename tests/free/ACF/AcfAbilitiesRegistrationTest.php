<?php

namespace WPMCP\Tests\Free\ACF;

/**
 * Verifies the three ACF abilities are registered as free-tier abilities
 * when ACF is active. Plugin::boot() registers abilities once at
 * wp_abilities_api_init against the ACF activation state already decided by
 * the test bootstrap, so (unlike the pro-tier gate, which is toggled per
 * test) this asserts directly against the live wp_get_abilities() registry,
 * matching WooCommerceAbilitiesRegistrationTest's approach for another
 * unconditionally-active-in-CI optional plugin.
 */
class AcfAbilitiesRegistrationTest extends \WP_UnitTestCase
{
    private const TOOLS = [
        'wpmcp/list-field-groups',
        'wpmcp/get-fields',
        'wpmcp/update-fields',
    ];

    public function test_all_acf_tools_are_registered_as_free_abilities_when_acf_active(): void
    {
        if (! wpmcp_acf_active()) {
            $this->markTestSkipped('ACF not active');
        }

        $names = array_keys(wp_get_abilities());

        foreach (self::TOOLS as $name) {
            $this->assertContains($name, $names, "Expected {$name} to be registered");
        }
    }

    public function test_acf_abilities_have_description_and_category(): void
    {
        if (! wpmcp_acf_active()) {
            $this->markTestSkipped('ACF not active');
        }

        $abilities = wp_get_abilities();

        foreach (self::TOOLS as $name) {
            $ability = $abilities[ $name ];
            $this->assertNotEmpty($ability->get_description(), "Expected {$name} to have a description");
            $this->assertSame('wpmcp', $ability->get_category());
        }
    }
}
