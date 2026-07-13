<?php

namespace WPMCP\Tests\Free\I18n;

/**
 * Verifies the i18n tools are registered as free-tier abilities only when a
 * multilingual plugin is active, following the conditional-registration
 * pattern used for the ACF and SEO tools. Plugin::boot() registers abilities
 * once at wp_abilities_api_init against the plugin activation state already
 * decided by the test bootstrap, so this asserts directly against the live
 * wp_get_abilities() registry.
 */
class I18nAbilitiesRegistrationTest extends \WP_UnitTestCase
{
    private const TOOLS = [
        'wpmcp/list-languages',
        'wpmcp/get-post-translations',
        'wpmcp/set-post-language',
    ];

    public function test_tools_are_registered_when_an_i18n_plugin_is_active(): void
    {
        if ('' === wpmcp_i18n_plugin()) {
            $this->markTestSkipped('No i18n plugin active');
        }

        $names = array_keys(wp_get_abilities());

        foreach (self::TOOLS as $tool) {
            $this->assertContains($tool, $names);
        }
    }

    public function test_tools_are_not_registered_when_no_i18n_plugin_is_active(): void
    {
        if ('' !== wpmcp_i18n_plugin()) {
            $this->markTestSkipped('An i18n plugin is active in this test environment.');
        }

        $names = array_keys(wp_get_abilities());

        foreach (self::TOOLS as $tool) {
            $this->assertNotContains($tool, $names);
        }
    }

    public function test_i18n_abilities_have_description_and_category(): void
    {
        if ('' === wpmcp_i18n_plugin()) {
            $this->markTestSkipped('No i18n plugin active');
        }

        $abilities = wp_get_abilities();

        foreach (self::TOOLS as $tool) {
            $ability = $abilities[$tool];
            $this->assertNotEmpty($ability->get_description(), "Expected {$tool} to have a description");
            $this->assertSame('wpmcp', $ability->get_category());
        }
    }
}
