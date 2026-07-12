<?php

namespace WPMCP\Tests\Free\Media;

use WPMCP\Tools\Media\Delete_Media;
use WPMCP\Safety\{Snapshot_Store, Rollback_Service};

class DeleteMediaTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Snapshot_Store::install();
    }

    public function test_disabled_by_default(): void
    {
        $id = self::factory()->attachment->create_object(['post_title' => 'Sunset']);

        $this->expectException(\RuntimeException::class);
        (new Delete_Media())->handle(['media_id' => $id, 'confirm' => true]);
    }
}
