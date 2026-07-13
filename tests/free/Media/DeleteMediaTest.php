<?php

namespace WPMCP\Tests\Free\Media;

use WPMCP\Tools\Media\Delete_Media;
use WPMCP\Safety\{File_Backup, Snapshot_Store, Rollback_Service};

class DeleteMediaTest extends \WP_UnitTestCase
{
    private array $cleanup_paths = [];

    protected function setUp(): void
    {
        parent::setUp();
        Snapshot_Store::install();
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanup_paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->cleanup_paths = [];
        parent::tearDown();
    }

    public function test_disabled_by_default(): void
    {
        $id = self::factory()->attachment->create_object(['post_title' => 'Sunset']);

        $this->expectException(\RuntimeException::class);
        (new Delete_Media())->handle(['media_id' => $id, 'confirm' => true]);
    }

    public function test_requires_confirm_when_enabled(): void
    {
        add_filter('wpmcp_enable_delete_media', '__return_true');
        $id = self::factory()->attachment->create_object(['post_title' => 'Sunset']);

        $this->expectException(\InvalidArgumentException::class);
        (new Delete_Media())->handle(['media_id' => $id]);
    }

    public function test_rejects_non_attachment_when_enabled(): void
    {
        add_filter('wpmcp_enable_delete_media', '__return_true');
        $id = self::factory()->post->create();

        $this->expectException(\InvalidArgumentException::class);
        (new Delete_Media())->handle(['media_id' => $id, 'confirm' => true]);
    }

    public function test_not_found_throws_when_enabled(): void
    {
        add_filter('wpmcp_enable_delete_media', '__return_true');

        $this->expectException(\InvalidArgumentException::class);
        (new Delete_Media())->handle(['media_id' => 999999, 'confirm' => true]);
    }

    public function test_requires_media_id_when_enabled(): void
    {
        add_filter('wpmcp_enable_delete_media', '__return_true');

        $this->expectException(\InvalidArgumentException::class);
        (new Delete_Media())->handle(['confirm' => true]);
    }

    /**
     * Write a minimal real image file at an upload-dir path and register an
     * attachment (with real intermediate-size metadata) pointing at it, so
     * these tests exercise real files on disk rather than mocks.
     */
    private function create_attachment_with_real_files(): array
    {
        $uploads = wp_upload_dir();
        $main    = trailingslashit($uploads['path']) . 'wpmcp-delete-media-test.jpg';
        $thumb   = trailingslashit($uploads['path']) . 'wpmcp-delete-media-test-150x150.jpg';

        wp_mkdir_p(dirname($main));
        file_put_contents($main, 'pretend-original-bytes');
        file_put_contents($thumb, 'pretend-thumbnail-bytes');
        $this->cleanup_paths[] = $main;
        $this->cleanup_paths[] = $thumb;

        $id = self::factory()->attachment->create_object([
            'file'           => $main,
            'post_mime_type' => 'image/jpeg',
            'post_title'     => 'Sunset',
        ]);

        wp_update_attachment_metadata($id, [
            'width'  => 300,
            'height' => 300,
            'file'   => _wp_relative_upload_path($main),
            'sizes'  => [
                'thumbnail' => [
                    'file'      => basename($thumb),
                    'width'     => 150,
                    'height'    => 150,
                    'mime-type' => 'image/jpeg',
                ],
            ],
        ]);

        return ['id' => $id, 'main' => $main, 'thumb' => $thumb];
    }

    /**
     * WordPress bypasses Trash for attachments unless MEDIA_TRASH is
     * defined truthy, which the default test environment does not define.
     * So without force:true this is still a real permanent delete, and it
     * must be safe-wrapped (snapshotted + rollback-able) rather than
     * silently unrecoverable, unlike Delete_Post's trash path which is
     * genuinely reversible via WordPress's own trash. The physical files
     * are backed up first, so rollback restores them along with the DB
     * record. See issue #24.
     */
    public function test_default_delete_without_media_trash_constant_is_permanent_and_safe_wrapped(): void
    {
        add_filter('wpmcp_enable_delete_media', '__return_true');
        $files = $this->create_attachment_with_real_files();
        $id    = $files['id'];

        $out = (new Delete_Media())->handle(['media_id' => $id, 'confirm' => true, 'session_id' => 's1']);

        $this->assertSame('deleted', $out['deleted']);
        $this->assertArrayHasKey('operation_id', $out);
        $this->assertNull(get_post($id));
        $this->assertNotNull(Snapshot_Store::get_by_operation($out['operation_id']));

        $this->assertTrue($out['files_recoverable']);
        $this->assertArrayNotHasKey('warning', $out);

        $this->assertFileDoesNotExist($files['main']);
        $this->assertFileDoesNotExist($files['thumb']);

        $this->assertTrue(Rollback_Service::restore_operation($out['operation_id']));
        $this->assertNotNull(get_post($id));

        $this->assertFileExists($files['main']);
        $this->assertSame('pretend-original-bytes', file_get_contents($files['main']));
        $this->assertFileExists($files['thumb']);
        $this->assertSame('pretend-thumbnail-bytes', file_get_contents($files['thumb']));

        File_Backup::delete_backup_dir($out['operation_id']);
    }

    public function test_force_delete_is_safe_wrapped_and_rollback_resurrects_attachment_and_files(): void
    {
        add_filter('wpmcp_enable_delete_media', '__return_true');
        $files = $this->create_attachment_with_real_files();
        $id    = $files['id'];
        update_post_meta($id, '_wp_attachment_image_alt', 'Sunset over the sea');
        wp_update_post(['ID' => $id, 'post_excerpt' => 'A caption']);

        $out = (new Delete_Media())->handle(['media_id' => $id, 'confirm' => true, 'force' => true, 'session_id' => 's1']);

        $this->assertSame('deleted', $out['deleted']);
        $this->assertNull(get_post($id));
        $this->assertNotNull(Snapshot_Store::get_by_operation($out['operation_id']));

        $this->assertTrue($out['files_recoverable']);
        $this->assertArrayNotHasKey('warning', $out);

        $this->assertFileDoesNotExist($files['main']);
        $this->assertFileDoesNotExist($files['thumb']);

        $this->assertTrue(Rollback_Service::restore_operation($out['operation_id']));

        $restored = get_post($id);
        $this->assertNotNull($restored);
        $this->assertSame('attachment', $restored->post_type);
        $this->assertSame('Sunset', $restored->post_title);
        $this->assertSame('A caption', $restored->post_excerpt);
        $this->assertSame('Sunset over the sea', get_post_meta($id, '_wp_attachment_image_alt', true));

        $this->assertFileExists($files['main']);
        $this->assertSame('pretend-original-bytes', file_get_contents($files['main']));
        $this->assertFileExists($files['thumb']);
        $this->assertSame('pretend-thumbnail-bytes', file_get_contents($files['thumb']));

        File_Backup::delete_backup_dir($out['operation_id']);
    }
}
