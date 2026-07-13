<?php

namespace WPMCP\Tests\Free\Safety;

use WPMCP\Safety\File_Backup;

class FileBackupTest extends \WP_UnitTestCase
{
    private array $created_files = [];

    protected function tearDown(): void
    {
        foreach ($this->created_files as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->created_files = [];
        parent::tearDown();
    }

    /**
     * Write a minimal real (1x1 GIF) image file at an upload-dir-relative
     * path, so tests exercise real files on disk rather than mocks.
     */
    private function write_real_file(string $abs): void
    {
        wp_mkdir_p(dirname($abs));
        // Smallest possible valid GIF, well-formed bytes (not just filler).
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBTAA7');
        file_put_contents($abs, $gif);
        $this->created_files[] = $abs;
    }

    private function make_attachment_with_files(): int
    {
        $uploads = wp_upload_dir();
        $main    = trailingslashit($uploads['path']) . 'sunset-original.jpg';
        $thumb   = trailingslashit($uploads['path']) . 'sunset-original-150x150.jpg';

        $this->write_real_file($main);
        $this->write_real_file($thumb);

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

        return $id;
    }

    public function test_collects_main_file_and_every_intermediate_size(): void
    {
        $id = $this->make_attachment_with_files();

        $files = File_Backup::collect_attachment_files($id);

        $main  = get_attached_file($id);
        $thumb = trailingslashit(dirname($main)) . 'sunset-original-150x150.jpg';

        $this->assertContains($main, $files);
        $this->assertContains($thumb, $files);
        $this->assertCount(2, $files);
    }

    public function test_deduplicates_and_skips_missing_size_files(): void
    {
        $id   = $this->make_attachment_with_files();
        $main = get_attached_file($id);

        // Add a size entry in metadata whose file was never written to disk.
        $meta                     = wp_get_attachment_metadata($id);
        $meta['sizes']['missing'] = [
            'file'      => 'does-not-exist-anywhere.jpg',
            'width'     => 50,
            'height'    => 50,
            'mime-type' => 'image/jpeg',
        ];
        wp_update_attachment_metadata($id, $meta);

        $files = File_Backup::collect_attachment_files($id);

        $missing_path = trailingslashit(dirname($main)) . 'does-not-exist-anywhere.jpg';
        $this->assertNotContains($missing_path, $files);
        $this->assertSame(array_values(array_unique($files)), array_values($files));
    }
}
