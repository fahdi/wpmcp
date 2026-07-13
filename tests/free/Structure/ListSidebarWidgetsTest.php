<?php

namespace WPMCP\Tests\Free\Structure;

use WPMCP\Tools\Structure\List_Sidebar_Widgets;

class ListSidebarWidgetsTest extends \WP_UnitTestCase
{
    public function tearDown(): void
    {
        unregister_sidebar('wpmcp-test-sidebar');

        $sidebars_widgets = wp_get_sidebars_widgets();
        unset($sidebars_widgets['wpmcp-test-sidebar']);
        wp_set_sidebars_widgets($sidebars_widgets);

        parent::tearDown();
    }

    public function test_returns_widgets_assigned_to_a_sidebar(): void
    {
        register_sidebar(['id' => 'wpmcp-test-sidebar', 'name' => 'WPMCP Test Sidebar']);

        global $wp_registered_widgets;
        $widget_ids = array_keys($wp_registered_widgets);
        $this->assertNotEmpty($widget_ids, 'Expected at least one default widget to be registered.');
        $widget_id = $widget_ids[0];

        $sidebars_widgets                          = wp_get_sidebars_widgets();
        $sidebars_widgets['wpmcp-test-sidebar'] = [$widget_id];
        wp_set_sidebars_widgets($sidebars_widgets);

        $out = (new List_Sidebar_Widgets())->handle(['sidebar_id' => 'wpmcp-test-sidebar']);

        $this->assertArrayHasKey('widgets', $out);
        $ids = array_column($out['widgets'], 'id');
        $this->assertContains($widget_id, $ids);

        $widget = null;
        foreach ($out['widgets'] as $row) {
            if ($widget_id === $row['id']) {
                $widget = $row;
                break;
            }
        }
        $this->assertNotNull($widget);
        $this->assertArrayHasKey('name', $widget);
    }

    public function test_throws_for_an_unknown_sidebar_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new List_Sidebar_Widgets())->handle(['sidebar_id' => 'not-a-real-sidebar']);
    }
}
