<?php

namespace WPMCP\Tests\Free\Media;

use WPMCP\Tools\Media\Get_Media;

class GetMediaTest extends \WP_UnitTestCase
{
    public function test_returns_full_attachment_detail(): void
    {
        $id = self::factory()->attachment->create_object([
            'file'           => 'sunset.jpg',
            'post_mime_type' => 'image/jpeg',
            'post_title'     => 'Sunset',
            'post_excerpt'   => 'A caption',
            'post_content'   => 'A description',
        ]);
        update_post_meta($id, '_wp_attachment_image_alt', 'Sunset over the sea');
        wp_update_attachment_metadata($id, [
            'width'  => 1200,
            'height' => 800,
            'file'   => 'sunset.jpg',
            'sizes'  => [
                'thumbnail' => ['file' => 'sunset-150x150.jpg', 'width' => 150, 'height' => 150, 'mime-type' => 'image/jpeg'],
            ],
        ]);

        $out = (new Get_Media())->handle(['media_id' => $id]);

        $this->assertSame($id, $out['media_id']);
        $this->assertSame('Sunset', $out['title']);
        $this->assertSame('A caption', $out['caption']);
        $this->assertSame('A description', $out['description']);
        $this->assertSame('Sunset over the sea', $out['alt']);
        $this->assertSame('image/jpeg', $out['mime_type']);
        $this->assertSame(1200, $out['width']);
        $this->assertSame(800, $out['height']);
        $this->assertArrayHasKey('thumbnail', $out['sizes']);
        $this->assertSame(150, $out['sizes']['thumbnail']['width']);
        $this->assertIsArray($out['metadata']);
    }

    public function test_not_found_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Media())->handle(['media_id' => 999999]);
    }

    public function test_requires_media_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Media())->handle([]);
    }

    public function test_rejects_non_attachment(): void
    {
        $id = self::factory()->post->create();
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Media())->handle(['media_id' => $id]);
    }
}
