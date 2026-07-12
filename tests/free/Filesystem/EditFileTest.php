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

    public function test_replaces_a_unique_match_and_backs_up_the_original(): void
    {
        add_filter('wpmcp_enable_fs_writes', '__return_true');
        $admin = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin);

        $result = (new Edit_File())->handle([
            'path'       => $this->rel_dir . '/edit.txt',
            'old_string' => 'world',
            'new_string' => 'there',
        ]);

        $this->assertSame(1, $result['replacements']);
        $this->assertTrue($result['recoverable']);
        $this->assertNotNull($result['backup']);
        $this->assertSame("hello there\n", file_get_contents(ABSPATH . $this->rel_dir . '/edit.txt'));

        $backup_abs = ABSPATH . $result['backup'];
        $this->assertSame("hello world\n", file_get_contents($backup_abs));

        $restored = \WPMCP\Tools\Filesystem\Filesystem_Guard::restore($backup_abs, ABSPATH . $this->rel_dir . '/edit.txt');
        $this->assertTrue($restored);
        $this->assertSame("hello world\n", file_get_contents(ABSPATH . $this->rel_dir . '/edit.txt'));

        @unlink($backup_abs);
    }
}
