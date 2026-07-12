<?php

namespace WPMCP\Tests\Free\Content;

use WPMCP\Tools\Content\List_Post_Types;

class ListPostTypesTest extends \WP_UnitTestCase
{
    public function test_returns_public_post_types_with_expected_shape(): void
    {
        $out   = (new List_Post_Types())->handle([]);
        $names = array_column($out['post_types'], 'name');

        $this->assertContains('post', $names);
        $this->assertContains('page', $names);

        $post_row = $out['post_types'][ array_search('post', $names, true) ];
        $this->assertArrayHasKey('label', $post_row);
        $this->assertArrayHasKey('hierarchical', $post_row);
        $this->assertArrayHasKey('public', $post_row);
        $this->assertArrayHasKey('taxonomies', $post_row);
    }

    public function test_excludes_internal_post_types(): void
    {
        $out   = (new List_Post_Types())->handle([]);
        $names = array_column($out['post_types'], 'name');

        $this->assertNotContains('revision', $names);
        $this->assertNotContains('nav_menu_item', $names);
        $this->assertNotContains('attachment', $names);
    }
}
