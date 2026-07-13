<?php

namespace WPMCP\Tests\Free\ACF;

use WPMCP\Tools\ACF\Get_Fields;

class GetFieldsTest extends \WP_UnitTestCase
{
    private array $created = [];

    protected function tearDown(): void
    {
        foreach ($this->created as $id) {
            wp_delete_post($id, true);
        }
        $this->created = [];
        parent::tearDown();
    }

    private function registerGroup(): void
    {
        acf_add_local_field_group([
            'key'      => 'group_wpmcp_test_get',
            'title'    => 'WPMCP Test Get Group',
            'fields'   => [
                [
                    'key'   => 'field_wpmcp_test_get_text',
                    'label' => 'Test Text',
                    'name'  => 'wpmcp_test_text',
                    'type'  => 'text',
                ],
            ],
            'location' => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'post',
                    ],
                ],
            ],
        ]);
    }

    private function post(): int
    {
        $id = $this->factory()->post->create();
        $this->created[] = $id;
        return $id;
    }

    public function test_returns_field_values_for_a_post(): void
    {
        if (! wpmcp_acf_active()) {
            $this->markTestSkipped('ACF not active');
        }

        $this->registerGroup();
        $post_id = $this->post();
        update_field('wpmcp_test_text', 'hello world', $post_id);

        $out = (new Get_Fields())->handle(['post_id' => $post_id]);

        $this->assertArrayHasKey('fields', $out);
        $this->assertSame('hello world', $out['fields']['wpmcp_test_text']);
    }

    public function test_returns_empty_fields_when_none_set(): void
    {
        if (! wpmcp_acf_active()) {
            $this->markTestSkipped('ACF not active');
        }

        $post_id = $this->post();

        $out = (new Get_Fields())->handle(['post_id' => $post_id]);

        $this->assertArrayHasKey('fields', $out);
        $this->assertSame([], $out['fields']);
    }

    public function test_requires_post_id(): void
    {
        if (! wpmcp_acf_active()) {
            $this->markTestSkipped('ACF not active');
        }

        $this->expectException(\InvalidArgumentException::class);
        (new Get_Fields())->handle([]);
    }
}
