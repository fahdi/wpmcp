<?php

namespace WPMCP\Tests\Pro\Elementor;

use WPMCP\Tools\Elementor\Reorder_Elements;
use WPMCP\Tools\Rollback_Operation;

/**
 * reorder-elements (issue #58): reorder the children of one parent (or the
 * top level) to an explicit id order. The order must be an exact
 * permutation of the current children; anything else is refused before any
 * write.
 */
class ReorderElementsTest extends Structural_Harness
{
    public function test_reorders_top_level_elements(): void
    {
        $post_id = $this->make_page();

        $out = (new Reorder_Elements())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'order'         => ['cont002', 'cont001'],
        ]);

        $this->assertIsArray($out);
        $this->assertArrayHasKey('operation_id', $out);

        $tree = $this->tree($post_id);
        $this->assertSame(['cont002', 'cont001'], array_column($tree, 'id'));
        $this->assert_builder_clean($post_id);
    }

    public function test_reorders_children_of_a_parent(): void
    {
        $post_id = $this->make_page();

        $out = (new Reorder_Elements())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'parent_id'     => 'cont001',
            'order'         => ['wid0002', 'wid0001'],
        ]);

        $this->assertIsArray($out);
        $parent = $this->find_in($this->tree($post_id), 'cont001');
        $this->assertSame(['wid0002', 'wid0001'], array_column($parent['elements'], 'id'));
    }

    public function test_refuses_order_that_is_not_a_permutation(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        $missing = (new Reorder_Elements())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'order'         => ['cont001'],
        ]);
        $this->assertInstanceOf(\WP_Error::class, $missing);
        $this->assertSame('invalid_order', $missing->get_error_code());

        $foreign = (new Reorder_Elements())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'order'         => ['cont001', 'no-such'],
        ]);
        $this->assertInstanceOf(\WP_Error::class, $foreign);
        $this->assertSame('invalid_order', $foreign->get_error_code());

        $this->assertSame($before, $this->raw($post_id));
    }

    public function test_refuses_unknown_parent(): void
    {
        $post_id = $this->make_page();

        $out = (new Reorder_Elements())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'parent_id'     => 'no-such',
            'order'         => ['wid0001'],
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('parent_not_found', $out->get_error_code());
    }

    public function test_stale_hash_refusal_writes_nothing(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        $out = (new Reorder_Elements())->handle([
            'post_id'       => $post_id,
            'expected_hash' => hash('sha256', 'stale'),
            'order'         => ['cont002', 'cont001'],
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('stale_expected_hash', $out->get_error_code());
        $this->assertSame($before, $this->raw($post_id));
    }

    public function test_rollback_operation_restores_original_order(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        $out = (new Reorder_Elements())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'order'         => ['cont002', 'cont001'],
        ]);

        $result = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($result['restored']);
        $this->assertSame($before, $this->raw($post_id));
    }
}
