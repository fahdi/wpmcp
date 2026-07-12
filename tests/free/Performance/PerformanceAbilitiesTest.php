<?php

namespace WPMCP\Tests\Free\Performance;

class PerformanceAbilitiesTest extends \WP_UnitTestCase
{
    public function test_analyze_performance_ability_is_registered(): void
    {
        $names = array_keys(wp_get_abilities());

        $this->assertContains('wpmcp/analyze-performance', $names);
    }

    public function test_analyze_performance_ability_has_description_and_category(): void
    {
        $ability = wp_get_abilities()['wpmcp/analyze-performance'];

        $this->assertNotEmpty($ability->get_description());
        $this->assertSame('wpmcp', $ability->get_category());
    }

    public function test_analyze_performance_denies_subscriber_and_allows_administrator(): void
    {
        $ability = wp_get_abilities()['wpmcp/analyze-performance'];

        $subscriber = self::factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber);
        $this->assertFalse($ability->check_permissions());

        $admin = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin);
        $this->assertTrue($ability->check_permissions());
    }
}
