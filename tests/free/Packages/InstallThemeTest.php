<?php

namespace WPMCP\Tests\Free\Packages;

use WPMCP\Tools\Packages\Install_Theme;

class InstallThemeTest extends \WP_UnitTestCase
{
    public function test_requires_slug(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Install_Theme())->handle([]);
    }

    public function test_rejects_non_wordpress_org_slug_formats(): void
    {
        foreach ([
            'https://example.com/evil.zip',
            '../../etc/passwd',
            'some/theme.zip',
            'theme with spaces',
        ] as $bad_slug) {
            try {
                (new Install_Theme())->handle(['slug' => $bad_slug]);
                $this->fail("Expected rejection for slug \"{$bad_slug}\".");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('slug', strtolower($e->getMessage()));
            }
        }
    }

    public function test_blocked_when_filesystem_not_direct(): void
    {
        add_filter('filesystem_method', fn () => 'ftpext');

        $this->expectException(\RuntimeException::class);
        (new Install_Theme())->handle(['slug' => 'astra']);
    }
}
