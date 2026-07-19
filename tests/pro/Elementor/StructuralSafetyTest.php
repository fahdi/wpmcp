<?php

namespace WPMCP\Tests\Pro\Elementor;

use WPMCP\Tools\Elementor\Add_Container;
use WPMCP\Tools\Elementor\Batch_Update;
use WPMCP\Tools\Elementor\Duplicate_Element;
use WPMCP\Tools\Elementor\Reorder_Elements;
use WPMCP\Tools\Elementor\Set_Element_Label;
use WPMCP\Tools\Elementor\Update_Container;
use WPMCP\Tools\Rollback_Operation;

/**
 * Cross-cutting safety guarantees of the structural suite (issue #58):
 * every structural op is restorable byte-identical via rollback-operation,
 * untouched elements' JSON survives every op byte-identical, Elementor's
 * generated-CSS cache is invalidated on every write, and when the
 * Document::save path is unavailable the raw-meta fallback still clears
 * the CSS cache.
 */
class StructuralSafetyTest extends Structural_Harness
{
    /** Every structural op as [tool instance, extra args beyond post_id/expected_hash]. */
    private function structural_ops(): array
    {
        return [
            'add-container'     => [new Add_Container(), []],
            'update-container'  => [new Update_Container(), ['element_id' => 'cont001', 'settings' => ['flex_direction' => 'row']]],
            'batch-update'      => [new Batch_Update(), ['updates' => [['element_id' => 'wid0001', 'settings' => ['title' => 'B']]]]],
            'reorder-elements'  => [new Reorder_Elements(), ['order' => ['cont002', 'cont001']]],
            'duplicate-element' => [new Duplicate_Element(), ['element_id' => 'cont002']],
            'set-element-label' => [new Set_Element_Label(), ['element_id' => 'cont001', 'label' => 'Hero']],
        ];
    }

    public function test_every_structural_op_is_restorable_via_rollback_operation(): void
    {
        foreach ($this->structural_ops() as $name => [$tool, $extra]) {
            $post_id = $this->make_page();
            $before  = $this->raw($post_id);

            $out = $tool->handle(array_merge([
                'post_id'       => $post_id,
                'expected_hash' => $this->data_hash($post_id),
            ], $extra));

            $this->assertIsArray($out, "{$name} must succeed");
            $this->assertNotSame($before, $this->raw($post_id), "{$name} must actually write");

            $result = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
            $this->assertTrue($result['restored'], "{$name} must be restorable");
            $this->assertSame($before, $this->raw($post_id), "{$name} rollback must be byte-identical");
        }
    }

    public function test_untouched_elements_json_survives_byte_identical(): void
    {
        $post_id = $this->make_page();

        // First structural write canonicalizes the stored JSON through
        // Elementor's own save path; from then on untouched elements must
        // survive every subsequent op byte-identical.
        $first = (new Update_Container())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'cont002',
            'settings'      => ['flex_direction' => 'column'],
        ]);
        $this->assertIsArray($first);

        $untouched_before = wp_json_encode($this->find_in($this->tree($post_id), 'cont001'));

        $second = (new Set_Element_Label())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'wid0003',
            'label'         => 'Buy Button',
        ]);
        $this->assertIsArray($second);

        $raw_after        = $this->raw($post_id);
        $untouched_after  = wp_json_encode($this->find_in($this->tree($post_id), 'cont001'));

        $this->assertSame($untouched_before, $untouched_after, 'Untouched subtree must re-serialize byte-identical.');
        $this->assertStringContainsString(
            $untouched_before,
            $raw_after,
            'The untouched subtree must appear byte-identical inside the stored JSON.'
        );
    }

    public function test_structural_write_clears_generated_css_cache(): void
    {
        $post_id = $this->make_page();
        update_post_meta($post_id, '_elementor_css', ['status' => 'stale-probe']);

        $out = (new Update_Container())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'cont001',
            'settings'      => ['flex_direction' => 'row'],
        ]);

        $this->assertIsArray($out);
        $this->assertEmpty(
            get_post_meta($post_id, '_elementor_css', true),
            'A structural write must invalidate the page\'s generated CSS.'
        );
    }

    public function test_raw_meta_fallback_still_writes_and_clears_css_cache(): void
    {
        $post_id = $this->make_page();
        update_post_meta($post_id, '_elementor_css', ['status' => 'stale-probe']);

        // No active kit means Elementor's Document::save path cannot run
        // (container controls dereference the kit); the engine must fall
        // back to a raw meta write WITH an explicit CSS cache clear.
        delete_option('elementor_active_kit');

        $out = (new Update_Container())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'cont001',
            'settings'      => ['flex_direction' => 'row'],
        ]);

        $this->assertIsArray($out);
        $container = $this->find_in($this->tree($post_id), 'cont001');
        $this->assertSame('row', $container['settings']['flex_direction']);
        $this->assertEmpty(
            get_post_meta($post_id, '_elementor_css', true),
            'The raw-meta fallback must still clear the generated-CSS cache.'
        );
    }

    public function test_builder_opens_clean_after_every_structural_op(): void
    {
        foreach ($this->structural_ops() as $name => [$tool, $extra]) {
            $post_id = $this->make_page();

            $out = $tool->handle(array_merge([
                'post_id'       => $post_id,
                'expected_hash' => $this->data_hash($post_id),
            ], $extra));

            $this->assertIsArray($out, "{$name} must succeed");
            $this->assert_builder_clean($post_id);
        }
    }
}
