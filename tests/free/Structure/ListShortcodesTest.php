<?php

namespace WPMCP\Tests\Free\Structure;

use WPMCP\Tools\Structure\List_Shortcodes;

class ListShortcodesTest extends \WP_UnitTestCase
{
    public function tearDown(): void
    {
        remove_shortcode('wpmcp_test');
        parent::tearDown();
    }

    public function test_includes_a_registered_shortcode(): void
    {
        add_shortcode('wpmcp_test', function () {
            return 'hello';
        });

        $out = (new List_Shortcodes())->handle([]);

        $tags = array_column($out['shortcodes'], 'tag');
        $this->assertContains('wpmcp_test', $tags);
    }

    public function test_search_filter_narrows_by_tag_substring(): void
    {
        add_shortcode('wpmcp_test', function () {
            return 'hello';
        });

        $out = (new List_Shortcodes())->handle(['search' => 'wpmcp_test']);

        $tags = array_column($out['shortcodes'], 'tag');
        $this->assertContains('wpmcp_test', $tags);
        $this->assertNotContains('gallery', $tags);
    }
}
