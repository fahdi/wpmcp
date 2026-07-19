<?php

namespace WPMCP\Tests\Pro\Elementor;

use WPMCP\Tools\Elementor\Set_Element_Label;
use WPMCP\Tools\Rollback_Operation;

/**
 * set-element-label (issue #58): set the element's navigator label, which
 * Elementor stores as the `_title` setting. An empty label clears it.
 */
class SetElementLabelTest extends Structural_Harness
{
    public function test_sets_label_as_title_setting(): void
    {
        $post_id = $this->make_page();

        $out = (new Set_Element_Label())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'cont001',
            'label'         => 'Hero Section',
        ]);

        $this->assertIsArray($out);
        $this->assertArrayHasKey('operation_id', $out);

        $element = $this->find_in($this->tree($post_id), 'cont001');
        $this->assertSame('Hero Section', $element['settings']['_title']);
        $this->assertSame('hero primary', $element['settings']['css_classes'], 'Other settings survive.');
    }

    public function test_empty_label_clears_existing_title(): void
    {
        $post_id = $this->make_page();

        (new Set_Element_Label())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'wid0001',
            'label'         => 'Named',
        ]);

        $out = (new Set_Element_Label())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'wid0001',
            'label'         => '',
        ]);

        $this->assertIsArray($out);
        $element = $this->find_in($this->tree($post_id), 'wid0001');
        $this->assertArrayNotHasKey('_title', $element['settings']);
    }

    public function test_refuses_unknown_element(): void
    {
        $post_id = $this->make_page();

        $out = (new Set_Element_Label())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'no-such',
            'label'         => 'X',
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('element_not_found', $out->get_error_code());
    }

    public function test_stale_hash_refusal_writes_nothing(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        $out = (new Set_Element_Label())->handle([
            'post_id'       => $post_id,
            'expected_hash' => hash('sha256', 'stale'),
            'element_id'    => 'cont001',
            'label'         => 'X',
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('stale_expected_hash', $out->get_error_code());
        $this->assertSame($before, $this->raw($post_id));
    }

    public function test_rollback_operation_restores_previous_label_state(): void
    {
        $post_id = $this->make_page();
        $before  = $this->raw($post_id);

        $out = (new Set_Element_Label())->handle([
            'post_id'       => $post_id,
            'expected_hash' => $this->data_hash($post_id),
            'element_id'    => 'cont001',
            'label'         => 'Hero Section',
        ]);

        $result = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($result['restored']);
        $this->assertSame($before, $this->raw($post_id));
    }
}
