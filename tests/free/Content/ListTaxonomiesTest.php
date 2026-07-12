<?php

namespace WPMCP\Tests\Free\Content;

use WPMCP\Tools\Content\List_Taxonomies;

class ListTaxonomiesTest extends \WP_UnitTestCase
{
    public function test_returns_taxonomies_with_expected_shape(): void
    {
        $out   = (new List_Taxonomies())->handle([]);
        $names = array_column($out['taxonomies'], 'name');

        $this->assertContains('category', $names);
        $this->assertContains('post_tag', $names);

        $category_row = $out['taxonomies'][ array_search('category', $names, true) ];
        $this->assertArrayHasKey('label', $category_row);
        $this->assertArrayHasKey('hierarchical', $category_row);
        $this->assertArrayHasKey('object_types', $category_row);
        $this->assertContains('post', $category_row['object_types']);
    }

    public function test_filters_by_post_type(): void
    {
        $out   = (new List_Taxonomies())->handle(['post_type' => 'post']);
        $names = array_column($out['taxonomies'], 'name');

        $this->assertContains('category', $names);
        $this->assertContains('post_tag', $names);
    }
}
