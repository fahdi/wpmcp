<?php

namespace WPMCP\Tests\Free\Menus;

use WPMCP\Tools\Menus\List_Menu_Locations;

class ListMenuLocationsTest extends \WP_UnitTestCase
{
    private array $menus = [];
    private $prior_locations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prior_locations = get_theme_mod('nav_menu_locations');
    }

    protected function tearDown(): void
    {
        if (false === $this->prior_locations) {
            remove_theme_mod('nav_menu_locations');
        } else {
            set_theme_mod('nav_menu_locations', $this->prior_locations);
        }
        unregister_nav_menu('wpmcp-test-loc');
        foreach ($this->menus as $id) {
            wp_delete_nav_menu($id);
        }
        $this->menus = [];
        parent::tearDown();
    }

    public function test_lists_registered_locations_with_assignment(): void
    {
        register_nav_menu('wpmcp-test-loc', 'WPMCP Test Location');
        $menu_id = wp_create_nav_menu('Assigned Menu');
        $this->menus[] = $menu_id;
        set_theme_mod('nav_menu_locations', ['wpmcp-test-loc' => $menu_id]);

        $out = (new List_Menu_Locations())->handle([]);

        $row = null;
        foreach ($out['locations'] as $candidate) {
            if ('wpmcp-test-loc' === $candidate['location']) {
                $row = $candidate;
                break;
            }
        }
        $this->assertNotNull($row, 'Expected the registered location to be listed');
        $this->assertSame('WPMCP Test Location', $row['description']);
        $this->assertSame($menu_id, $row['menu_id']);
    }
}
