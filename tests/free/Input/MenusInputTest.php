<?php

namespace WPMCP\Tests\Free\Input;

use WPMCP\Tools\Menus\Add_Menu_Item;
use WPMCP\Tools\Menus\Update_Menu_Item;
use WPMCP\Tools\Menus\Remove_Menu_Item;
use WPMCP\Tools\Menus\Assign_Menu_To_Location;
use WPMCP\Tools\Menus\Delete_Menu;

/**
 * Input-boundary tests for the Menus domain: missing/invalid menu or item
 * ids, unknown theme locations, and missing confirm/disabled gates must
 * all fail cleanly (InvalidArgumentException/RuntimeException), never a
 * fatal or a silent no-op.
 */
class MenusInputTest extends \WP_UnitTestCase
{
    public function test_add_menu_item_rejects_missing_menu_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Add_Menu_Item())->handle(['title' => 'Home', 'url' => '/']);
    }

    public function test_add_menu_item_rejects_nonexistent_menu_id(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Add_Menu_Item())->handle(['menu_id' => 999999999, 'title' => 'Home', 'url' => '/']);
    }

    public function test_update_menu_item_rejects_missing_item_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Update_Menu_Item())->handle(['title' => 'x']);
    }

    public function test_update_menu_item_rejects_nonexistent_item_id(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Update_Menu_Item())->handle(['item_id' => 999999999, 'title' => 'x']);
    }

    public function test_remove_menu_item_rejects_missing_item_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Remove_Menu_Item())->handle([]);
    }

    public function test_remove_menu_item_rejects_nonexistent_item_id(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Remove_Menu_Item())->handle(['item_id' => 999999999]);
    }

    public function test_remove_menu_item_rejects_a_post_id_that_is_not_a_menu_item(): void
    {
        $id = self::factory()->post->create();

        $this->expectException(\RuntimeException::class);
        (new Remove_Menu_Item())->handle(['item_id' => $id]);
    }

    public function test_assign_menu_to_location_rejects_missing_menu_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Assign_Menu_To_Location())->handle(['location' => 'primary']);
    }

    public function test_assign_menu_to_location_rejects_missing_location(): void
    {
        $menu_id = wp_create_nav_menu('Test Menu ' . uniqid());

        $this->expectException(\InvalidArgumentException::class);
        (new Assign_Menu_To_Location())->handle(['menu_id' => $menu_id]);
    }

    public function test_assign_menu_to_location_rejects_unregistered_location(): void
    {
        $menu_id = wp_create_nav_menu('Test Menu ' . uniqid());

        $this->expectException(\InvalidArgumentException::class);
        (new Assign_Menu_To_Location())->handle(['menu_id' => $menu_id, 'location' => 'this-location-does-not-exist']);
    }

    public function test_assign_menu_to_location_rejects_nonexistent_menu(): void
    {
        register_nav_menu('wpmcp_test_location', 'Test Location');

        $this->expectException(\RuntimeException::class);
        (new Assign_Menu_To_Location())->handle(['menu_id' => 999999999, 'location' => 'wpmcp_test_location']);
    }

    public function test_delete_menu_disabled_by_default(): void
    {
        $menu_id = wp_create_nav_menu('Test Menu ' . uniqid());

        $this->expectException(\RuntimeException::class);
        (new Delete_Menu())->handle(['id' => $menu_id, 'confirm' => true]);
    }

    public function test_delete_menu_requires_confirm_when_enabled(): void
    {
        add_filter('wpmcp_enable_delete_menu', '__return_true');
        $menu_id = wp_create_nav_menu('Test Menu ' . uniqid());

        $this->expectException(\InvalidArgumentException::class);
        (new Delete_Menu())->handle(['id' => $menu_id]);
    }

    public function test_delete_menu_rejects_nonexistent_id_when_enabled(): void
    {
        add_filter('wpmcp_enable_delete_menu', '__return_true');

        $this->expectException(\RuntimeException::class);
        (new Delete_Menu())->handle(['id' => 999999999, 'confirm' => true]);
    }
}
