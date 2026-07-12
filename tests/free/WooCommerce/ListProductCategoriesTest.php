<?php

namespace WPMCP\Tests\Free\WooCommerce;

use WPMCP\Tools\WooCommerce\List_Product_Categories;

class ListProductCategoriesTest extends \WP_UnitTestCase
{
    private array $terms = [];

    protected function tearDown(): void
    {
        foreach ($this->terms as $term_id) {
            wp_delete_term($term_id, 'product_cat');
        }
        $this->terms = [];
        parent::tearDown();
    }

    public function test_lists_product_categories(): void
    {
        if (! wpmcp_woocommerce_active()) {
            $this->markTestSkipped('WooCommerce not active');
        }

        $term = wp_insert_term('Homeware', 'product_cat');
        $this->terms[] = $term['term_id'];

        $out = (new List_Product_Categories())->handle([]);

        $this->assertArrayHasKey('categories', $out);
        $names = array_column($out['categories'], 'name');
        $this->assertContains('Homeware', $names);

        $row = $out['categories'][array_search('Homeware', $names, true)];
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('slug', $row);
        $this->assertArrayHasKey('count', $row);
    }
}
