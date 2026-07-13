<?php

namespace WPMCP\Tests\Free\Input;

use WPMCP\Tools\Media\Get_Media;
use WPMCP\Tools\Media\Update_Media;
use WPMCP\Tools\Media\Sideload_Image;

/**
 * Input-boundary tests for the Media domain: missing/invalid media ids,
 * wrong object type (a post id that isn't an attachment), and missing
 * required args must fail cleanly, never a fatal or silent wrong result.
 */
class MediaInputTest extends \WP_UnitTestCase
{
    public function test_get_media_rejects_missing_media_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Media())->handle([]);
    }

    public function test_get_media_rejects_zero_media_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Media())->handle(['media_id' => 0]);
    }

    public function test_get_media_rejects_nonexistent_media_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Media())->handle(['media_id' => 999999999]);
    }

    public function test_get_media_rejects_a_post_id_that_is_not_an_attachment(): void
    {
        $id = self::factory()->post->create();
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Media())->handle(['media_id' => $id]);
    }

    public function test_update_media_rejects_missing_media_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Update_Media())->handle(['title' => 'x']);
    }

    public function test_update_media_rejects_nonexistent_media_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Update_Media())->handle(['media_id' => 999999999, 'title' => 'x']);
    }

    public function test_update_media_rejects_a_post_id_that_is_not_an_attachment(): void
    {
        $id = self::factory()->post->create();
        $this->expectException(\InvalidArgumentException::class);
        (new Update_Media())->handle(['media_id' => $id, 'title' => 'x']);
    }

    public function test_sideload_image_rejects_missing_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Sideload_Image())->handle([]);
    }

    public function test_sideload_image_rejects_empty_string_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Sideload_Image())->handle(['url' => '   ']);
    }
}
