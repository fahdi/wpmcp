<?php

namespace WPMCP\Tests\Free\Packages;

use WPMCP\Tools\Packages\Update_Theme;

class UpdateThemeTest extends \WP_UnitTestCase
{
    public function test_disabled_by_default(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Update_Theme())->handle(['stylesheet' => 'twentytwentythree', 'confirm' => true]);
    }

    public function test_requires_confirm_when_enabled(): void
    {
        add_filter('wpmcp_enable_update_theme', '__return_true');

        $this->expectException(\InvalidArgumentException::class);
        (new Update_Theme())->handle(['stylesheet' => 'twentytwentythree']);
    }

    public function test_requires_stylesheet_argument_when_enabled(): void
    {
        add_filter('wpmcp_enable_update_theme', '__return_true');

        $this->expectException(\InvalidArgumentException::class);
        (new Update_Theme())->handle(['confirm' => true]);
    }

    public function test_unknown_theme_errors_when_enabled(): void
    {
        add_filter('wpmcp_enable_update_theme', '__return_true');

        $this->expectException(\RuntimeException::class);
        (new Update_Theme())->handle(['stylesheet' => 'ghost-theme', 'confirm' => true]);
    }

    public function test_reports_up_to_date_when_no_update_available(): void
    {
        add_filter('wpmcp_enable_update_theme', '__return_true');
        set_site_transient('update_themes', (object) ['response' => []]);

        $out = (new Update_Theme())->handle(['stylesheet' => 'twentytwentythree', 'confirm' => true]);

        $this->assertTrue($out['up_to_date']);
        $this->assertFalse($out['updated']);
    }
}
