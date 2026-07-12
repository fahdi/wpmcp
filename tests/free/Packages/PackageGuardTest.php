<?php

namespace WPMCP\Tests\Free\Packages;

use WPMCP\Tools\Packages\Package_Guard;

class PackageGuardTest extends \WP_UnitTestCase
{
    public function test_protects_wpmcp_and_elementor_plugin_slugs(): void
    {
        $this->assertTrue(Package_Guard::is_protected_plugin('wpmcp/wpmcp.php'));
        $this->assertTrue(Package_Guard::is_protected_plugin('elementor/elementor.php'));
        $this->assertTrue(Package_Guard::is_protected_plugin('elementor-pro/elementor-pro.php'));
    }

    public function test_does_not_protect_unrelated_plugin_slug(): void
    {
        $this->assertFalse(Package_Guard::is_protected_plugin('akismet/akismet.php'));
    }

    public function test_filesystem_ready_true_for_direct_method(): void
    {
        add_filter('filesystem_method', fn () => 'direct');
        $this->assertTrue(Package_Guard::filesystem_ready());
    }
}
