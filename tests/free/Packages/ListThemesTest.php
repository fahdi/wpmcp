<?php

namespace WPMCP\Tests\Free\Packages;

use WPMCP\Tools\Packages\List_Themes;

class ListThemesTest extends \WP_UnitTestCase
{
    public function test_lists_themes_marking_the_active_one(): void
    {
        $active = get_stylesheet();

        $out = (new List_Themes())->handle([]);

        $this->assertArrayHasKey('themes', $out);
        $this->assertNotEmpty($out['themes']);

        $rows = [];
        foreach ($out['themes'] as $row) {
            $rows[ $row['stylesheet'] ] = $row;
        }

        $this->assertArrayHasKey($active, $rows);
        $this->assertTrue($rows[ $active ]['is_active']);
        $this->assertArrayHasKey('name', $rows[ $active ]);
        $this->assertArrayHasKey('version', $rows[ $active ]);
    }

    public function test_non_active_theme_is_marked_inactive(): void
    {
        $out = (new List_Themes())->handle([]);

        $active = get_stylesheet();
        foreach ($out['themes'] as $row) {
            if ($row['stylesheet'] !== $active) {
                $this->assertFalse($row['is_active']);
                return;
            }
        }

        $this->markTestSkipped('Only one theme installed in this environment.');
    }
}
