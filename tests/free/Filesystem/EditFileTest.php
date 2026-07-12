<?php

namespace WPMCP\Tests\Free\Filesystem;

use WPMCP\Tools\Filesystem\Edit_File;

class EditFileTest extends \WP_UnitTestCase
{
    private string $rel_dir = 'wp-content/wpmcp-fs-test';

    public function setUp(): void
    {
        parent::setUp();
        mkdir(ABSPATH . $this->rel_dir, 0777, true);
        file_put_contents(ABSPATH . $this->rel_dir . '/edit.txt', "hello world\n");
    }

    public function tearDown(): void
    {
        @unlink(ABSPATH . $this->rel_dir . '/edit.txt');
        @rmdir(ABSPATH . $this->rel_dir);
        parent::tearDown();
    }

    public function test_disabled_by_default(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Edit_File())->handle([
            'path'       => $this->rel_dir . '/edit.txt',
            'old_string' => 'world',
            'new_string' => 'there',
        ]);
    }
}
