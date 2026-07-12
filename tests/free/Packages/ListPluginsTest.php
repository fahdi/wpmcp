<?php

namespace WPMCP\Tests\Free\Packages;

use WPMCP\Tools\Packages\List_Plugins;

class ListPluginsTest extends \WP_UnitTestCase
{
    public function test_lists_plugins_with_active_and_protected_flags(): void
    {
        $out = (new List_Plugins())->handle([]);

        $this->assertArrayHasKey('plugins', $out);
        $this->assertNotEmpty($out['plugins']);

        $rows = [];
        foreach ($out['plugins'] as $row) {
            $rows[ $row['file'] ] = $row;
        }

        $this->assertArrayHasKey('akismet/akismet.php', $rows);
        $this->assertFalse($rows['akismet/akismet.php']['is_protected']);
        $this->assertArrayHasKey('active', $rows['akismet/akismet.php']);
        $this->assertArrayHasKey('name', $rows['akismet/akismet.php']);
        $this->assertArrayHasKey('version', $rows['akismet/akismet.php']);
    }

    public function test_marks_active_plugin_correctly(): void
    {
        activate_plugin('akismet/akismet.php');

        $out  = (new List_Plugins())->handle([]);
        $rows = [];
        foreach ($out['plugins'] as $row) {
            $rows[ $row['file'] ] = $row;
        }

        $this->assertTrue($rows['akismet/akismet.php']['active']);

        deactivate_plugins(['akismet/akismet.php']);
    }
}
