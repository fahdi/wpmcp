<?php

namespace WPMCP\Tests\Free\Cli;

use WPMCP\Tools\Cli\Wp_Cli_Guard;

/**
 * Defense-in-depth: even though the executor builds argv as an array and
 * runs it via proc_open (never a shell string), every argument is rejected
 * up front if it contains a shell metacharacter or a NUL byte. This guards
 * against any future refactor that might reintroduce a shell string, and
 * against a NUL byte truncating an argument in ways some C library calls
 * mishandle.
 */
class WpCliGuardArgsTest extends \WP_UnitTestCase
{
    public function test_accepts_ordinary_args(): void
    {
        $result = Wp_Cli_Guard::validate_args(['plugin', 'list', '--status=active', '--format=json']);
        $this->assertTrue($result);
    }

    /** @dataProvider dangerous_arguments */
    public function test_rejects_dangerous_argument(string $arg): void
    {
        $result = Wp_Cli_Guard::validate_args(['plugin', 'list', $arg]);
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('wp_cli_unsafe_argument', $result->get_error_code());
    }

    public function dangerous_arguments(): array
    {
        return [
            'semicolon'        => ['foo; rm -rf /'],
            'pipe'             => ['foo | cat /etc/passwd'],
            'ampersand'        => ['foo & background'],
            'dollar'           => ['$(whoami)'],
            'backtick'         => ['`whoami`'],
            'redirect out'     => ['foo > /tmp/x'],
            'redirect in'      => ['foo < /etc/passwd'],
            'paren open'       => ['foo(bar)'],
            'brace open'       => ['foo{bar}'],
            'newline'          => ["foo\nbar"],
            'nul byte'         => ["foo\0bar"],
        ];
    }

    public function test_rejects_when_any_single_argument_is_dangerous(): void
    {
        $result = Wp_Cli_Guard::validate_args(['plugin', 'list', 'safe-arg', '$(evil)']);
        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    public function test_rejects_empty_argv(): void
    {
        $result = Wp_Cli_Guard::validate_args([]);
        $this->assertInstanceOf(\WP_Error::class, $result);
    }
}
