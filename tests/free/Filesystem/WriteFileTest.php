<?php

namespace WPMCP\Tests\Free\Filesystem;

use WPMCP\Tools\Filesystem\Write_File;

class WriteFileTest extends \WP_UnitTestCase
{
    private string $rel_dir = 'wp-content/wpmcp-fs-test';

    public function tearDown(): void
    {
        @unlink(ABSPATH . $this->rel_dir . '/new.txt');
        @rmdir(ABSPATH . $this->rel_dir);
        parent::tearDown();
    }

    public function test_disabled_by_default(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Write_File())->handle(['path' => $this->rel_dir . '/new.txt', 'content' => 'hello']);
    }
}
