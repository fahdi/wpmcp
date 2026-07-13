<?php

namespace WPMCP\Tests\Free\Cli;

use WPMCP\Tools\Cli\Wp_Cli_Guard;

/**
 * Only explicitly allowlisted wp subcommands may run. Deny-by-default: an
 * argv whose leading subcommand words are not in the allowlist is rejected
 * regardless of any trailing flags/arguments. The default set is
 * conservative and read-only-ish (core version, plugin list, theme list,
 * option get, cache flush, cron event list, user list); sites may extend it
 * via the wpmcp_wp_cli_allowlist filter, but the default set is always the
 * conservative one unless a filter changes it.
 */
class WpCliGuardAllowlistTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        remove_all_filters('wpmcp_wp_cli_allowlist');
        parent::tearDown();
    }

    /** @dataProvider default_allowed_subcommands */
    public function test_default_allowlist_accepts_expected_subcommands(array $argv): void
    {
        $this->assertTrue(Wp_Cli_Guard::is_allowed_subcommand($argv));
    }

    public function default_allowed_subcommands(): array
    {
        return [
            'core version'        => [['core', 'version']],
            'plugin list'          => [['plugin', 'list']],
            'theme list'           => [['theme', 'list']],
            'option get'           => [['option', 'get', 'siteurl']],
            'cache flush'          => [['cache', 'flush']],
            'cron event list'      => [['cron', 'event', 'list']],
            'user list'            => [['user', 'list']],
            'plugin list w/ flags' => [['plugin', 'list', '--status=active', '--format=json']],
        ];
    }

    public function test_rejects_subcommand_not_on_allowlist(): void
    {
        $this->assertFalse(Wp_Cli_Guard::is_allowed_subcommand(['plugin', 'delete', 'akismet']));
    }

    public function test_rejects_db_query_even_though_option_get_is_allowed(): void
    {
        $this->assertFalse(Wp_Cli_Guard::is_allowed_subcommand(['db', 'query', 'DROP TABLE wp_users']));
    }

    public function test_rejects_empty_argv(): void
    {
        $this->assertFalse(Wp_Cli_Guard::is_allowed_subcommand([]));
    }

    public function test_rejects_a_prefix_of_an_allowed_entry_only(): void
    {
        // "cron" alone is not "cron event list"; a shorter argv must not
        // match a longer allowlist entry.
        $this->assertFalse(Wp_Cli_Guard::is_allowed_subcommand(['cron']));
        $this->assertFalse(Wp_Cli_Guard::is_allowed_subcommand(['cron', 'event']));
    }

    public function test_allowlist_is_filterable(): void
    {
        add_filter('wpmcp_wp_cli_allowlist', function (array $allowlist): array {
            $allowlist[] = 'plugin delete';
            return $allowlist;
        });

        $this->assertTrue(Wp_Cli_Guard::is_allowed_subcommand(['plugin', 'delete', 'akismet']));
    }

    public function test_filter_can_narrow_the_default_allowlist(): void
    {
        add_filter('wpmcp_wp_cli_allowlist', function (): array {
            return ['core version'];
        });

        $this->assertTrue(Wp_Cli_Guard::is_allowed_subcommand(['core', 'version']));
        $this->assertFalse(Wp_Cli_Guard::is_allowed_subcommand(['plugin', 'list']));
    }

    public function test_default_allowlist_contents(): void
    {
        $this->assertSame(
            [
                'core version',
                'plugin list',
                'theme list',
                'option get',
                'cache flush',
                'cron event list',
                'user list',
            ],
            Wp_Cli_Guard::default_allowlist()
        );
    }
}
