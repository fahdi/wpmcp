<?php

namespace WPMCP\Tests\Free\Settings;

use WPMCP\Tools\Settings\Get_Settings;

class GetSettingsTest extends \WP_UnitTestCase
{
    private array $original_options = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['blogname', 'blogdescription', 'admin_email', 'posts_per_page', 'blog_public', 'show_on_front', 'permalink_structure'] as $key) {
            $this->original_options[ $key ] = get_option($key);
        }
        update_option('blogname', 'My Site');
    }

    protected function tearDown(): void
    {
        foreach ($this->original_options as $key => $value) {
            update_option($key, $value);
        }
        parent::tearDown();
    }

    private function find(array $out, string $key): ?array
    {
        foreach ($out['settings'] as $row) {
            if ($row['key'] === $key) {
                return $row;
            }
        }
        return null;
    }

    public function test_get_all_returns_rows_with_metadata(): void
    {
        $out = (new Get_Settings())->handle([]);
        $this->assertArrayHasKey('settings', $out);

        $row = $this->find($out, 'blogname');
        $this->assertNotNull($row);
        $this->assertSame('general', $row['group']);
        $this->assertSame('string', $row['type']);
        $this->assertSame('My Site', $row['value']);
        $this->assertTrue($row['writable']);
    }
}
