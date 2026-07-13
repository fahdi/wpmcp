<?php

namespace WPMCP\Tests\Free\Export;

use WPMCP\Tools\Export\List_Exports;
use WPMCP\Tools\Export\Export_Dir;

class ListExportsTest extends \WP_UnitTestCase
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

    /** Write a fixture export file directly, without going through Export_Content::handle(). */
    private function write_fixture_export(string $filename, string $contents = '<rss></rss>'): string
    {
        $dir = Export_Dir::path();
        Export_Dir::protect($dir);
        $path = trailingslashit($dir) . $filename;
        file_put_contents($path, $contents);
        $this->cleanup_files[] = $path;
        return $path;
    }

    public function test_lists_a_generated_export_file(): void
    {
        $this->write_fixture_export('wpmcp-export-test-1.xml', '<rss>hello</rss>');

        $out = (new List_Exports())->handle([]);

        $names = array_column($out['exports'], 'name');
        $this->assertContains('wpmcp-export-test-1.xml', $names);
    }

    public function test_returns_size_and_created_for_each_export(): void
    {
        $this->write_fixture_export('wpmcp-export-test-2.xml', '<rss>twelve!</rss>');

        $out = (new List_Exports())->handle([]);

        $row = null;
        foreach ($out['exports'] as $candidate) {
            if ('wpmcp-export-test-2.xml' === $candidate['name']) {
                $row = $candidate;
            }
        }

        $this->assertNotNull($row);
        $this->assertSame(strlen('<rss>twelve!</rss>'), $row['size']);
        $this->assertArrayHasKey('created', $row);
        $this->assertNotEmpty($row['created']);
    }

    public function test_excludes_protection_files_from_the_listing(): void
    {
        $this->write_fixture_export('wpmcp-export-test-3.xml');

        $out = (new List_Exports())->handle([]);

        $names = array_column($out['exports'], 'name');
        $this->assertNotContains('.htaccess', $names);
        $this->assertNotContains('index.php', $names);
    }

    public function test_empty_directory_returns_empty_list(): void
    {
        $dir = Export_Dir::path();
        if (is_dir($dir)) {
            foreach (array_diff((array) scandir($dir), ['.', '..']) as $entry) {
                unlink(trailingslashit($dir) . $entry);
            }
            rmdir($dir);
        }

        $out = (new List_Exports())->handle([]);

        $this->assertSame([], $out['exports']);
    }
}
