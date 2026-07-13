<?php

namespace WPMCP\Tests\Free\Cli;

use WPMCP\Tools\Cli\Wp_Cli_Guard;

/**
 * Issue #44 security review, finding C1 (CRITICAL): the subcommand allowlist
 * only matched the leading positional words and let every trailing argv
 * token through unchecked, so an allowlisted prefix plus a wp-cli GLOBAL
 * flag (--require, --exec, --ssh, --http, --path, --url, ...) was a full
 * allowlist bypass to arbitrary PHP execution / host pivot, because wp-cli
 * itself interprets those flags before the guarded subcommand ever runs.
 *
 * Fix: deny-by-default on flags. Any argv token beginning with "-" must be
 * on a narrow, filterable safe-flag allowlist (default: --format, --fields,
 * --field) or the whole command is rejected before the executor is ever
 * invoked. This is validated over the FULL token stream, not just the
 * trailing words after the matched subcommand prefix.
 */
class WpCliGuardFlagAllowlistTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        remove_all_filters('wpmcp_wp_cli_flag_allowlist');
        parent::tearDown();
    }

    /** @dataProvider dangerous_global_flags */
    public function test_rejects_dangerous_wp_cli_global_flag(array $argv): void
    {
        $result = Wp_Cli_Guard::validate_flags($argv);
        $this->assertInstanceOf(\WP_Error::class, $result, 'Expected ' . implode(' ', $argv) . ' to be rejected.');
        $this->assertSame('wp_cli_disallowed_flag', $result->get_error_code());
    }

    public function dangerous_global_flags(): array
    {
        return [
            'require (file inclusion RCE)' => [['core', 'version', '--require=/tmp/evil.php']],
            'require with space form'      => [['core', 'version', '--require', '/tmp/evil.php']],
            'exec (inline PHP RCE)'        => [['core', 'version', '--exec=include("/tmp/x")']],
            'bare --exec'                  => [['core', 'version', '--exec']],
            'ssh (remote host pivot)'      => [['plugin', 'list', '--ssh=attacker@evil.tld']],
            'http (retarget install)'      => [['core', 'version', '--http=http://evil.tld']],
            'path (retarget install)'      => [['core', 'version', '--path=/tmp/x']],
            'url'                          => [['core', 'version', '--url=evil.tld']],
            'user (identity override)'     => [['core', 'version', '--user=1']],
            'bare --eval'                  => [['core', 'version', '--eval']],
            'prompt'                       => [['core', 'version', '--prompt']],
            'debug'                        => [['core', 'version', '--debug']],
            'unknown flag'                 => [['core', 'version', '--whatever']],
            'short flag'                   => [['core', 'version', '-x']],
        ];
    }

    public function test_allowlisted_format_flag_is_still_permitted(): void
    {
        $result = Wp_Cli_Guard::validate_flags(['plugin', 'list', '--format=json']);
        $this->assertTrue($result);
    }

    public function test_allowlisted_fields_and_field_flags_are_still_permitted(): void
    {
        $this->assertTrue(Wp_Cli_Guard::validate_flags(['plugin', 'list', '--fields=name,status']));
        $this->assertTrue(Wp_Cli_Guard::validate_flags(['plugin', 'list', '--field=status']));
    }

    public function test_purely_positional_allowlisted_command_is_still_permitted(): void
    {
        $this->assertTrue(Wp_Cli_Guard::validate_flags(['core', 'version']));
        $this->assertTrue(Wp_Cli_Guard::validate_flags(['cron', 'event', 'list']));
    }

    public function test_flag_allowlist_is_filterable(): void
    {
        add_filter('wpmcp_wp_cli_flag_allowlist', function (array $allowed): array {
            $allowed[] = '--status';
            return $allowed;
        });

        $this->assertTrue(Wp_Cli_Guard::validate_flags(['plugin', 'list', '--status=active']));
    }

    public function test_default_flag_allowlist_contents(): void
    {
        $this->assertSame(
            ['--format', '--fields', '--field'],
            Wp_Cli_Guard::default_flag_allowlist()
        );
    }

    public function test_rejects_argument_containing_a_space(): void
    {
        // Each argv element is already a discrete array entry (never a shell
        // string), but a space inside one element implies an attempt to
        // smuggle what looks like a second token/flag past validation via a
        // single element, so it is rejected outright regardless of content.
        $result = Wp_Cli_Guard::validate_flags(['core', 'version', 'a b']);
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('wp_cli_unsafe_argument', $result->get_error_code());
    }

    public function test_full_run_wp_cli_end_to_end_exploit_is_blocked(): void
    {
        // End-to-end confirmation of the exploit from the security review,
        // exercised through the full guard chain (not just validate_flags in
        // isolation), using Wp_Cli_Guard directly as Run_Wp_Cli::guard() does.
        $this->assertTrue(Wp_Cli_Guard::is_allowed_subcommand(['core', 'version', '--require=/tmp/evil.php']));

        $flags_result = Wp_Cli_Guard::validate_flags(['core', 'version', '--require=/tmp/evil.php']);
        $this->assertInstanceOf(\WP_Error::class, $flags_result, 'The --require RCE payload must be rejected by validate_flags even though is_allowed_subcommand only checks the leading words.');
    }
}
