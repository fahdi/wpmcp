<?php

namespace WPMCP\Tests\Free\Menus;

use WPMCP\Tools\Menus\List_Menus;

class ListMenusTest extends \WP_UnitTestCase
{
    private array $menus = [];

    protected function tearDown(): void
    {
        foreach ($this->menus as $id) {
            wp_delete_nav_menu($id);
        }
        $this->menus = [];
        parent::tearDown();
    }

    private function menu(string $name): int
    {
        $id = wp_create_nav_menu($name);
        $this->menus[] = $id;
        return $id;
    }

    public function test_lists_menus_as_summary_rows(): void
    {
        $id = $this->menu('Primary Nav');

        $out = (new List_Menus())->handle([]);

        $this->assertArrayHasKey('menus', $out);
        $row = null;
        foreach ($out['menus'] as $candidate) {
            if ((int) $candidate['id'] === $id) {
                $row = $candidate;
                break;
            }
        }
        $this->assertNotNull($row, 'Expected the created menu to appear in the list');
        $this->assertSame('Primary Nav', $row['name']);
        $this->assertArrayHasKey('slug', $row);
        $this->assertArrayHasKey('count', $row);
    }
}
