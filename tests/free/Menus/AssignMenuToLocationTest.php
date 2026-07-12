<?php

namespace WPMCP\Tests\Free\Menus;

use WPMCP\Tools\Menus\Assign_Menu_To_Location;
use WPMCP\Tools\Rollback_Operation;
use WPMCP\Safety\Snapshot_Store;

class AssignMenuToLocationTest extends \WP_UnitTestCase
{
    private array $menus = [];
    private $prior_locations;

    protected function setUp(): void
    {
        parent::setUp();
        Snapshot_Store::install();
        $this->prior_locations = get_theme_mod('nav_menu_locations');
        register_nav_menu('wpmcp-assign-loc', 'WPMCP Assign Location');
    }

    protected function tearDown(): void
    {
        if (false === $this->prior_locations) {
            remove_theme_mod('nav_menu_locations');
        } else {
            set_theme_mod('nav_menu_locations', $this->prior_locations);
        }
        unregister_nav_menu('wpmcp-assign-loc');
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

    public function test_assigns_menu_to_location(): void
    {
        $menu_id = $this->menu('Assignable Menu');

        $out = (new Assign_Menu_To_Location())->handle([
            'menu_id'  => $menu_id,
            'location' => 'wpmcp-assign-loc',
        ]);

        $this->assertSame($menu_id, $out['menu_id']);
        $this->assertSame('wpmcp-assign-loc', $out['location']);

        $locations = get_theme_mod('nav_menu_locations');
        $this->assertSame($menu_id, $locations['wpmcp-assign-loc']);
    }

    public function test_assignment_is_recoverable_via_option_snapshot(): void
    {
        $first  = $this->menu('First Menu');
        $second = $this->menu('Second Menu');

        set_theme_mod('nav_menu_locations', ['wpmcp-assign-loc' => $first]);

        $out = (new Assign_Menu_To_Location())->handle([
            'menu_id'  => $second,
            'location' => 'wpmcp-assign-loc',
        ]);

        $this->assertTrue($out['recoverable']);
        $this->assertSame($second, get_theme_mod('nav_menu_locations')['wpmcp-assign-loc']);

        $rolled_back = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($rolled_back['restored']);

        $this->assertSame($first, get_theme_mod('nav_menu_locations')['wpmcp-assign-loc']);
    }

    public function test_unregistered_location_throws(): void
    {
        $menu_id = $this->menu('Menu X');
        $this->expectException(\InvalidArgumentException::class);
        (new Assign_Menu_To_Location())->handle([
            'menu_id'  => $menu_id,
            'location' => 'not-a-registered-location',
        ]);
    }

    public function test_missing_menu_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Assign_Menu_To_Location())->handle([
            'menu_id'  => 999999,
            'location' => 'wpmcp-assign-loc',
        ]);
    }
}
