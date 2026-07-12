<?php

namespace WPMCP\Tests\Pro\Elementor;

use WPMCP\Pro\Gate;
use WPMCP\Safety\{Rollback_Service, Snapshot_Store};
use WPMCP\Tools\Elementor\Add_Widget;

class AddWidgetTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Gate::set_pro_for_tests(true);
        Snapshot_Store::install();
    }

    protected function tearDown(): void
    {
        Gate::set_pro_for_tests(null);
        parent::tearDown();
    }

    private function make_page(): int
    {
        $post_id = self::factory()->post->create(['post_type' => 'page']);
        update_post_meta($post_id, '_elementor_data', wp_json_encode([
            [
                'id'       => 'sect001',
                'elType'   => 'section',
                'settings' => [],
                'elements' => [],
            ],
        ]));
        return $post_id;
    }

    public function test_inserts_widget_under_parent(): void
    {
        if (! wpmcp_elementor_active()) {
            $this->markTestSkipped('Elementor not active');
        }

        $post_id = $this->make_page();

        $out = (new Add_Widget())->handle([
            'post_id'     => $post_id,
            'parent_id'   => 'sect001',
            'widget_type' => 'heading',
            'settings'    => ['title' => 'New Heading'],
        ]);

        $this->assertArrayHasKey('operation_id', $out);
        $this->assertArrayHasKey('element_id', $out);
        $this->assertIsString($out['element_id']);
        $this->assertNotEmpty($out['element_id']);

        $raw = json_decode(get_post_meta($post_id, '_elementor_data', true), true);
        $child = $raw[0]['elements'][0];
        $this->assertSame($out['element_id'], $child['id']);
        $this->assertSame('widget', $child['elType']);
        $this->assertSame('heading', $child['widgetType']);
        $this->assertSame('New Heading', $child['settings']['title']);
    }

    public function test_rollback_removes_inserted_widget(): void
    {
        if (! wpmcp_elementor_active()) {
            $this->markTestSkipped('Elementor not active');
        }

        $post_id = $this->make_page();
        $before  = get_post_meta($post_id, '_elementor_data', true);

        $out = (new Add_Widget())->handle([
            'post_id'     => $post_id,
            'parent_id'   => 'sect001',
            'widget_type' => 'heading',
            'settings'    => ['title' => 'New Heading'],
        ]);

        Rollback_Service::restore_operation($out['operation_id']);

        $after = get_post_meta($post_id, '_elementor_data', true);
        $this->assertSame($before, $after);
    }

    public function test_returns_wp_error_when_post_id_missing(): void
    {
        if (! wpmcp_elementor_active()) {
            $this->markTestSkipped('Elementor not active');
        }

        $out = (new Add_Widget())->handle(['parent_id' => 'sect001', 'widget_type' => 'heading']);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('missing_post_id', $out->get_error_code());
    }

    public function test_returns_wp_error_when_widget_type_missing(): void
    {
        if (! wpmcp_elementor_active()) {
            $this->markTestSkipped('Elementor not active');
        }

        $post_id = $this->make_page();
        $out     = (new Add_Widget())->handle(['post_id' => $post_id, 'parent_id' => 'sect001']);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('missing_widget_type', $out->get_error_code());
    }

    public function test_returns_wp_error_for_unknown_widget_type(): void
    {
        if (! wpmcp_elementor_active()) {
            $this->markTestSkipped('Elementor not active');
        }

        $post_id = $this->make_page();
        $out     = (new Add_Widget())->handle([
            'post_id'     => $post_id,
            'parent_id'   => 'sect001',
            'widget_type' => 'totally-fake-widget',
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('invalid_widget_type', $out->get_error_code());
    }

    public function test_returns_wp_error_when_parent_not_found(): void
    {
        if (! wpmcp_elementor_active()) {
            $this->markTestSkipped('Elementor not active');
        }

        $post_id = $this->make_page();
        $out     = (new Add_Widget())->handle([
            'post_id'     => $post_id,
            'parent_id'   => 'does-not-exist',
            'widget_type' => 'heading',
        ]);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('parent_not_found', $out->get_error_code());
    }
}
