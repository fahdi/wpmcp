<?php

namespace WPMCP\Tests\Free\Elementor;

use WPMCP\Tools\Elementor\List_Widgets;

class ListWidgetsTest extends \WP_UnitTestCase
{
    public function test_lists_core_widgets_with_expected_shape(): void
    {
        if (! wpmcp_elementor_active()) {
            $this->markTestSkipped('Elementor not active');
        }

        $out = (new List_Widgets())->handle([]);

        $this->assertArrayHasKey('widgets', $out);

        $names = array_column($out['widgets'], 'name');
        $this->assertContains('heading', $names);
        $this->assertContains('image', $names);
        $this->assertContains('button', $names);

        $heading = $out['widgets'][array_search('heading', $names, true)];
        $this->assertSame('Heading', $heading['title']);
        $this->assertContains('basic', $heading['categories']);
        $this->assertSame('free', $heading['tier']);
        $this->assertArrayHasKey('icon', $heading);
        $this->assertNotEmpty($heading['icon']);
    }
}
