<?php

namespace WPMCP\Tests\Pro\Elementor;

use WPMCP\Tools\Elementor\Update_Container;
use WPMCP\Tools\Rollback_Operation;

/**
 * update-container (issue #58): non-destructive settings merge on a
 * container/section/column by id. Widgets are refused (update-element is
 * the widget path); the merge never drops settings keys it was not given.
 */
class UpdateContainerTest extends Structural_Harness
{
    public function test_merges_settings_non_destructively(): void
    {
        $post_id = $this->make_page();

        $out = (new Update_Container())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'cont001',
            'settings'      => ['flex_direction' => 'row', 'gap' => ['size' => 20, 'unit' => 'px']],
        ]);

        $this->assertIsArray($out);
        $this->assertArrayHasKey('operation_id', $out);
        $this->assertSame($this->data_hash($post_id), $out['data_hash']);

        $container = $this->find_in($this->tree($post_id), 'cont001');
        $this->assertSame('row', $container['settings']['flex_direction'], 'Given keys are overwritten.');
        $this->assertSame(['size' => 20, 'unit' => 'px'], $container['settings']['gap'], 'New keys are added.');
        $this->assertSame('hero primary', $container['settings']['css_classes'], 'Untouched keys survive the merge.');
    }

    public function test_refuses_widget_target(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        $out = (new Update_Container())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'wid0001',
            'settings'      => ['title' => 'X'],
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('not_a_container', $out->get_error_code());
        $this->assertSame($before, $this->raw($post_id));
    }

    public function test_refuses_unknown_element(): void
    {
        $post_id = $this->make_page();

        $out = (new Update_Container())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'no-such',
            'settings'      => ['flex_direction' => 'row'],
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('element_not_found', $out->get_error_code());
    }

    public function test_stale_hash_refusal_writes_nothing(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        $out = (new Update_Container())->handle([
            'post_id'       => $post_id,
            'expected_hash' => hash('sha256', 'stale'),
            'element_id'    => 'cont001',
            'settings'      => ['flex_direction' => 'row'],
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('stale_expected_hash', $out->get_error_code());
        $this->assertSame($before, $this->raw($post_id));
    }

    public function test_rollback_operation_restores_previous_settings(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        $out = (new Update_Container())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'cont001',
            'settings'      => ['flex_direction' => 'row'],
        ]);

        $this->assertNotSame($before, $this->raw($post_id));
        $result = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($result['restored']);
        $this->assertSame($before, $this->raw($post_id));
    }
}
