<?php

namespace WPMCP\Tests\Pro\Elementor;

use WPMCP\Tools\Elementor\Duplicate_Element;
use WPMCP\Tools\Rollback_Operation;

/**
 * duplicate-element (issue #58): deep-copy an element (and its whole
 * subtree) with recursively regenerated ids, inserted immediately after the
 * original among its siblings. The builder must open the result without
 * warnings: unique 7-char ids, every element instantiable.
 */
class DuplicateElementTest extends Structural_Harness
{
    public function test_duplicates_a_widget_after_the_original(): void
    {
        $post_id = $this->make_page();

        $out = (new Duplicate_Element())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'wid0001',
        ]);

        $this->assertIsArray($out);
        $this->assertArrayHasKey('new_element_id', $out);
        $this->assertNotSame('wid0001', $out['new_element_id']);

        $parent = $this->find_in($this->tree($post_id), 'cont001');
        $this->assertSame(
            ['wid0001', $out['new_element_id'], 'wid0002'],
            array_column($parent['elements'], 'id'),
            'The copy must sit immediately after the original.'
        );

        $copy = $this->find_in($this->tree($post_id), $out['new_element_id']);
        $this->assertSame('heading', $copy['widgetType']);
        $this->assertSame('Hello', $copy['settings']['title']);
        $this->assert_builder_clean($post_id);
    }

    public function test_duplicates_a_container_with_recursively_fresh_ids(): void
    {
        $post_id = $this->make_page();
        $original_ids = $this->all_ids($this->tree($post_id));

        $out = (new Duplicate_Element())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'cont001',
        ]);

        $this->assertIsArray($out);
        $tree = $this->tree($post_id);
        $this->assertSame(
            ['cont001', $out['new_element_id'], 'cont002'],
            array_column($tree, 'id')
        );

        $copy = $this->find_in($tree, $out['new_element_id']);
        $this->assertCount(2, $copy['elements'], 'The whole subtree is copied.');
        $this->assertSame('heading', $copy['elements'][0]['widgetType']);

        $copy_ids = $this->all_ids([$copy]);
        $this->assertSame([], array_intersect($copy_ids, $original_ids), 'Every copied id must be fresh.');
        $this->assert_builder_clean($post_id);
    }

    public function test_refuses_unknown_element(): void
    {
        $post_id = $this->make_page();

        $out = (new Duplicate_Element())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'no-such',
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('element_not_found', $out->get_error_code());
    }

    public function test_stale_hash_refusal_writes_nothing(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        $out = (new Duplicate_Element())->handle([
            'post_id'       => $post_id,
            'expected_hash' => hash('sha256', 'stale'),
            'element_id'    => 'wid0001',
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('stale_expected_hash', $out->get_error_code());
        $this->assertSame($before, $this->raw($post_id));
    }

    public function test_rollback_operation_removes_the_duplicate(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        $out = (new Duplicate_Element())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'cont002',
        ]);

        $this->assertNotSame($before, $this->raw($post_id));
        $result = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($result['restored']);
        $this->assertSame($before, $this->raw($post_id));
    }
}
