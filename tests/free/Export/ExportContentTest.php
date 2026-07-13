<?php

namespace WPMCP\Tests\Free\Export;

use WPMCP\Tools\Export\Export_Content;

/**
 * WordPress core's own export_wp() (wp-admin/includes/export.php) declares
 * several helper functions (wxr_cdata(), wxr_authors_list(), etc.) inside its
 * own function body with no idempotency guard, so calling it more than once
 * in the same PHP process is a fatal "cannot redeclare function" error. This
 * is a real constraint of WordPress core itself, not specific to this tool:
 * export_wp() was written for the one-shot Tools > Export admin-post request
 * lifecycle. Exercise every export_wp()-calling assertion from a single
 * handle() call per test method (never two), and cover the in-process
 * repeat-call case explicitly as its own assertion.
 */
class ExportContentTest extends \WP_UnitTestCase
{
    private array $cleanup_files = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanup_files as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->cleanup_files = [];
        parent::tearDown();
    }

    public function test_creates_a_wxr_file_containing_the_post_title_in_a_protected_directory(): void
    {
        $post_id = self::factory()->post->create([
            'post_title'   => 'WPMCP Export Fixture Post',
            'post_content' => 'Hello from the export test.',
            'post_status'  => 'publish',
        ]);

        $out = (new Export_Content())->handle([]);
        $this->cleanup_files[] = $out['file'];

        $this->assertFileExists($out['file']);
        $this->assertGreaterThan(0, $out['size']);
        $this->assertGreaterThanOrEqual(1, $out['item_count']);

        $xml = file_get_contents($out['file']);
        $this->assertStringContainsString('WPMCP Export Fixture Post', $xml);
        $this->assertStringContainsString('<rss', $xml);

        $this->assertNotNull(get_post($post_id));

        $dir = dirname($out['file']);
        $this->assertFileExists($dir . '/.htaccess');
        $this->assertFileExists($dir . '/index.php');
    }
}
