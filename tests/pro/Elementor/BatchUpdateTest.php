<?php

namespace WPMCP\Tests\Pro\Elementor;

use WPMCP\Tools\Elementor\Batch_Update;
use WPMCP\Tools\List_Operations;
use WPMCP\Tools\Rollback_Operation;

/**
 * batch-update (issue #58): N element settings updates applied atomically
 * under ONE snapshot. Atomicity is the load-bearing guarantee, so the
 * failure modes are specified first: any invalid target refuses the whole
 * batch before anything is written, a mid-save failure leaves the page
 * untouched, and a write that stores something other than the intended
 * tree is rolled back in full.
 */
class BatchUpdateTest extends Structural_Harness
{
    public function test_refuses_whole_batch_when_any_element_id_is_unknown_and_writes_nothing(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        $out = (new Batch_Update())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'updates'       => [
                ['element_id' => 'wid0001', 'settings' => ['title' => 'Changed']],
                ['element_id' => 'no-such', 'settings' => ['title' => 'Never']],
            ],
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('element_not_found', $out->get_error_code());
        $this->assertStringContainsString('no-such', $out->get_error_message());
        $this->assertSame($before, $this->raw($post_id), 'A refused batch must write nothing at all.');
    }

    public function test_mid_save_failure_rolls_the_whole_batch_back(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        add_filter('elementor/document/save/data', function () {
            throw new \RuntimeException('simulated mid-save failure');
        }, 100);

        $out = (new Batch_Update())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'updates'       => [
                ['element_id' => 'wid0001', 'settings' => ['title' => 'Changed']],
                ['element_id' => 'wid0003', 'settings' => ['text' => 'Changed']],
            ],
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('mutation_failed', $out->get_error_code());
        $this->assertSame($before, $this->raw($post_id), 'A failed batch must leave the page byte-identical.');
    }

    public function test_write_that_stores_the_wrong_tree_is_rolled_back_in_full(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        // Simulate a third-party save filter silently dropping an element:
        // the stored tree no longer matches the intended tree, so the
        // engine's verify step must restore the pre-batch state.
        add_filter('elementor/document/save/data', function ($data) {
            if (isset($data['elements']) && is_array($data['elements']) && count($data['elements']) > 1) {
                array_pop($data['elements']);
            }
            return $data;
        }, 100);

        $out = (new Batch_Update())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'updates'       => [
                ['element_id' => 'wid0001', 'settings' => ['title' => 'Changed']],
                ['element_id' => 'wid0003', 'settings' => ['text' => 'Changed']],
            ],
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('mutation_failed', $out->get_error_code());
        $this->assertSame($before, $this->raw($post_id), 'A partially-applied batch must be rolled back in full.');
    }

    public function test_applies_all_updates_under_one_snapshot(): void
    {
        $post_id    = $this->make_page();
        $session_id = 'batch-session-' . uniqid();

        $out = (new Batch_Update())->handle([
            'post_id'       => $post_id,
            'session_id'    => $session_id,
            'expected_hash' => $this->data_hash($post_id),
            'updates'       => [
                ['element_id' => 'wid0001', 'settings' => ['title' => 'New Title']],
                ['element_id' => 'wid0003', 'settings' => ['text' => 'New Text']],
                ['element_id' => 'cont002', 'settings' => ['flex_direction' => 'column']],
            ],
        ]);

        $this->assertIsArray($out);
        $this->assertArrayHasKey('operation_id', $out);
        $this->assertSame(3, $out['updated_count']);
        $this->assertSame($this->data_hash($post_id), $out['data_hash']);

        $tree = $this->tree($post_id);
        $this->assertSame('New Title', $this->find_in($tree, 'wid0001')['settings']['title']);
        $this->assertSame('New Text', $this->find_in($tree, 'wid0003')['settings']['text']);
        $this->assertSame('column', $this->find_in($tree, 'cont002')['settings']['flex_direction']);

        // Merge is non-destructive: untouched settings keys survive.
        $this->assertSame('headline', $this->find_in($tree, 'wid0001')['settings']['_css_classes']);

        $ops = (new List_Operations())->handle(['session_id' => $session_id]);
        $this->assertSame(1, $ops['total_count'], 'The whole batch must run under exactly one snapshot.');
        $this->assertSame('batch-update', $ops['operations'][0]['tool_name']);
    }

    public function test_single_rollback_operation_undoes_the_entire_batch(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        $out = (new Batch_Update())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'updates'       => [
                ['element_id' => 'wid0001', 'settings' => ['title' => 'New Title']],
                ['element_id' => 'wid0003', 'settings' => ['text' => 'New Text']],
            ],
        ]);

        $this->assertIsArray($out);
        $this->assertNotSame($before, $this->raw($post_id));

        $result = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($result['restored']);
        $this->assertSame($before, $this->raw($post_id));
    }

    public function test_requires_expected_hash(): void
    {
        $post_id = $this->make_page();

        $out = (new Batch_Update())->handle([
            'post_id' => $post_id,
            'updates' => [['element_id' => 'wid0001', 'settings' => ['title' => 'X']]],
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('missing_expected_hash', $out->get_error_code());
    }

    public function test_stale_expected_hash_is_a_structured_refusal_with_no_partial_write(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        $out = (new Batch_Update())->handle([
            'post_id'       => $post_id,
            'expected_hash' => hash('sha256', 'something-else'),
            'updates'       => [['element_id' => 'wid0001', 'settings' => ['title' => 'X']]],
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('stale_expected_hash', $out->get_error_code());
        $this->assertSame($before, $this->raw($post_id));
    }

    public function test_refuses_empty_updates(): void
    {
        $post_id = $this->make_page();

        $out = (new Batch_Update())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'updates'       => [],
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('missing_updates', $out->get_error_code());
    }
}
