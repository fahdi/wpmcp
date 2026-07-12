<?php

namespace WPMCP\Tests\Free\Menus;

use WPMCP\Tools\Menus\Create_Menu;

class CreateMenuTest extends \WP_UnitTestCase
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

    public function test_creates_a_menu(): void
    {
        $out = (new Create_Menu())->handle(['name' => 'Brand New Menu']);
        $this->menus[] = $out['id'];

        $this->assertGreaterThan(0, $out['id']);
        $this->assertSame('Brand New Menu', $out['name']);

        $menu = wp_get_nav_menu_object($out['id']);
        $this->assertSame('Brand New Menu', $menu->name);
    }

    public function test_requires_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Create_Menu())->handle([]);
    }

    public function test_duplicate_name_throws(): void
    {
        $id = wp_create_nav_menu('Existing Menu');
        $this->menus[] = $id;

        $this->expectException(\RuntimeException::class);
        (new Create_Menu())->handle(['name' => 'Existing Menu']);
    }
}
