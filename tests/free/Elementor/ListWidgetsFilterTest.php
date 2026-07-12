<?php

namespace WPMCP\Tests\Free\Elementor;

use WPMCP\Tools\Elementor\List_Widgets;

class ListWidgetsFilterTest extends \WP_UnitTestCase
{
    public function test_tier_filter_narrows_to_free_widgets_only(): void
    {
        if (! wpmcp_elementor_active()) {
            $this->markTestSkipped('Elementor not active');
        }

        $out   = (new List_Widgets())->handle(['tier' => 'free']);
        $names = array_column($out['widgets'], 'name');

        $this->assertContains('heading', $names);

        $tiers = array_unique(array_column($out['widgets'], 'tier'));
        $this->assertSame(['free'], $tiers);
    }

    public function test_category_filter_narrows_results(): void
    {
        if (! wpmcp_elementor_active()) {
            $this->markTestSkipped('Elementor not active');
        }

        $out   = (new List_Widgets())->handle(['category' => 'basic']);
        $names = array_column($out['widgets'], 'name');

        $this->assertContains('heading', $names);
        $this->assertContains('button', $names);

        foreach ($out['widgets'] as $widget) {
            $this->assertContains('basic', $widget['categories']);
        }
    }

    public function test_search_filter_matches_name_or_title(): void
    {
        if (! wpmcp_elementor_active()) {
            $this->markTestSkipped('Elementor not active');
        }

        $out   = (new List_Widgets())->handle(['search' => 'head']);
        $names = array_column($out['widgets'], 'name');

        $this->assertContains('heading', $names);
        $this->assertNotContains('button', $names);
    }
}
