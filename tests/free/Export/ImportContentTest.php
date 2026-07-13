<?php

namespace WPMCP\Tests\Free\Export;

use WPMCP\Tools\Export\Import_Content;

class ImportContentTest extends \WP_UnitTestCase
{
    private array $cleanup_files = [];
    private array $cleanup_posts = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanup_files as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->cleanup_files = [];
        foreach ($this->cleanup_posts as $post_id) {
            wp_delete_post($post_id, true);
        }
        $this->cleanup_posts = [];
        parent::tearDown();
    }

    private function write_wxr_fixture(string $title = 'WPMCP Import Fixture Post'): string
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0"
    xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:wp="http://wordpress.org/export/1.2/"
>
<channel>
    <title>Test Site</title>
    <item>
        <title>{$title}</title>
        <content:encoded><![CDATA[Hello from the import test.]]></content:encoded>
        <wp:post_type>post</wp:post_type>
        <wp:status>publish</wp:status>
        <wp:postmeta>
            <wp:meta_key>wpmcp_test_key</wp:meta_key>
            <wp:meta_value><![CDATA[wpmcp_test_value]]></wp:meta_value>
        </wp:postmeta>
    </item>
</channel>
</rss>
XML;

        $path = wp_tempnam('wpmcp-import-test');
        rename($path, $path . '.xml');
        $path .= '.xml';
        file_put_contents($path, $xml);
        $this->cleanup_files[] = $path;
        return $path;
    }

    public function test_refused_when_import_disabled(): void
    {
        $file = $this->write_wxr_fixture();

        $this->expectException(\RuntimeException::class);
        (new Import_Content())->handle(['file' => $file, 'confirm' => true]);
    }

    public function test_requires_confirm_when_enabled(): void
    {
        add_filter('wpmcp_enable_import', '__return_true');
        $file = $this->write_wxr_fixture();

        $this->expectException(\InvalidArgumentException::class);
        (new Import_Content())->handle(['file' => $file]);
    }

    public function test_imports_a_post_from_wxr_when_enabled_and_confirmed(): void
    {
        add_filter('wpmcp_enable_import', '__return_true');
        $file = $this->write_wxr_fixture('WPMCP Import Fixture Post Confirmed');

        $out = (new Import_Content())->handle(['file' => $file, 'confirm' => true]);

        $this->assertSame(1, $out['imported_count']);
        $this->assertCount(1, $out['created_post_ids']);
        $this->assertFalse($out['recoverable']);

        $post_id = $out['created_post_ids'][0];
        $this->cleanup_posts[] = $post_id;

        $post = get_post($post_id);
        $this->assertNotNull($post);
        $this->assertSame('WPMCP Import Fixture Post Confirmed', $post->post_title);
        $this->assertSame('publish', $post->post_status);
        $this->assertSame('post', $post->post_type);
        $this->assertStringContainsString('Hello from the import test.', $post->post_content);
        $this->assertSame('wpmcp_test_value', get_post_meta($post_id, 'wpmcp_test_key', true));
    }

    public function test_rejects_missing_file(): void
    {
        add_filter('wpmcp_enable_import', '__return_true');

        $this->expectException(\InvalidArgumentException::class);
        (new Import_Content())->handle(['file' => '/nonexistent/wpmcp-does-not-exist.xml', 'confirm' => true]);
    }
}
