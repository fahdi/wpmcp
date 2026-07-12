<?php

namespace WPMCP\Tests\Free\Packages;

use WPMCP\Tools\Packages\Delete_Plugin;

class DeletePluginTest extends \WP_UnitTestCase
{
    public function test_disabled_by_default(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Delete_Plugin())->handle(['plugin' => 'hello.php', 'confirm' => true]);
    }

    public function test_requires_confirm_when_enabled(): void
    {
        add_filter('wpmcp_enable_delete_plugin', '__return_true');

        $this->expectException(\InvalidArgumentException::class);
        (new Delete_Plugin())->handle(['plugin' => 'hello.php']);
    }

    public function test_refuses_protected_plugin_when_enabled(): void
    {
        add_filter('wpmcp_enable_delete_plugin', '__return_true');

        $this->expectException(\RuntimeException::class);
        (new Delete_Plugin())->handle(['plugin' => 'wpmcp/wpmcp.php', 'confirm' => true]);
    }

    public function test_refuses_active_plugin_when_enabled(): void
    {
        add_filter('wpmcp_enable_delete_plugin', '__return_true');
        activate_plugin('akismet/akismet.php');

        try {
            $this->expectException(\RuntimeException::class);
            (new Delete_Plugin())->handle(['plugin' => 'akismet/akismet.php', 'confirm' => true]);
        } finally {
            deactivate_plugins(['akismet/akismet.php'], true);
        }
    }

    public function test_requires_plugin_argument_when_enabled(): void
    {
        add_filter('wpmcp_enable_delete_plugin', '__return_true');

        $this->expectException(\InvalidArgumentException::class);
        (new Delete_Plugin())->handle(['confirm' => true]);
    }

    public function test_unknown_plugin_errors_when_enabled(): void
    {
        add_filter('wpmcp_enable_delete_plugin', '__return_true');

        $this->expectException(\RuntimeException::class);
        (new Delete_Plugin())->handle(['plugin' => 'ghost/ghost.php', 'confirm' => true]);
    }
}
