<?php

namespace WPMCP\Tests\Free\Content;

use WPMCP\Tools\Content\Get_Post;

class GetPostTest extends \WP_UnitTestCase
{
    public function test_returns_shape_and_is_elementor_flag(): void
    {
        $id = self::factory()->post->create([
            'post_type'    => 'page',
            'post_title'   => 'P',
            'post_content' => '<p>c</p>',
        ]);

        $out = (new Get_Post())->handle(['post_id' => $id]);

        $this->assertSame($id, $out['post_id']);
        $this->assertSame('page', $out['post_type']);
        $this->assertSame('P', $out['title']);
        $this->assertSame('<p>c</p>', $out['content']);
        $this->assertArrayHasKey('is_elementor', $out);
        $this->assertFalse($out['is_elementor']);
    }
}
