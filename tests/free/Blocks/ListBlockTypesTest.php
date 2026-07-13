<?php

namespace WPMCP\Tests\Free\Blocks;

use WPMCP\Tools\Blocks\List_Block_Types;

class ListBlockTypesTest extends \WP_UnitTestCase
{
    public function test_includes_known_core_block_types(): void
    {
        $out = (new List_Block_Types())->handle([]);

        $this->assertArrayHasKey('block_types', $out);

        $names = array_column($out['block_types'], 'name');
        $this->assertContains('core/paragraph', $names);
        $this->assertContains('core/heading', $names);
    }

    public function test_each_block_type_reports_core_fields(): void
    {
        $out = (new List_Block_Types())->handle([]);

        $paragraph = null;
        foreach ($out['block_types'] as $row) {
            if ('core/paragraph' === $row['name']) {
                $paragraph = $row;
                break;
            }
        }

        $this->assertNotNull($paragraph);
        $this->assertArrayHasKey('title', $paragraph);
        $this->assertArrayHasKey('category', $paragraph);
        $this->assertArrayHasKey('is_dynamic', $paragraph);
        $this->assertArrayHasKey('attributes', $paragraph);
        $this->assertIsArray($paragraph['attributes']);
        $this->assertContains('content', $paragraph['attributes']);
    }

    public function test_search_filter_narrows_by_name_substring(): void
    {
        $out = (new List_Block_Types())->handle(['search' => 'heading']);

        $names = array_column($out['block_types'], 'name');
        $this->assertContains('core/heading', $names);
        $this->assertNotContains('core/paragraph', $names);
    }

    public function test_category_filter_narrows_to_matching_category(): void
    {
        $all = (new List_Block_Types())->handle([]);

        $paragraph_category = null;
        foreach ($all['block_types'] as $row) {
            if ('core/paragraph' === $row['name']) {
                $paragraph_category = $row['category'];
                break;
            }
        }

        $this->assertNotNull($paragraph_category);

        $out = (new List_Block_Types())->handle(['category' => $paragraph_category]);
        foreach ($out['block_types'] as $row) {
            $this->assertSame($paragraph_category, $row['category']);
        }
        $names = array_column($out['block_types'], 'name');
        $this->assertContains('core/paragraph', $names);
    }
}
