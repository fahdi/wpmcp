<?php

namespace WPMCP\Tests\Free\Code;

use WPMCP\Tools\Code\Php_Snippet_Validator;

class PhpSnippetValidatorTest extends \WP_UnitTestCase
{
    public function test_valid_snippet_reports_syntax_valid_and_safe(): void
    {
        $result = Php_Snippet_Validator::validate('<?php echo 1 + 1;');

        $this->assertTrue($result['syntax_valid']);
        $this->assertSame([], $result['errors']);
        $this->assertTrue($result['safe']);
    }

    public function test_syntax_error_snippet_reports_invalid_with_error(): void
    {
        $result = Php_Snippet_Validator::validate("<?php echo 'unterminated;");

        $this->assertFalse($result['syntax_valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertArrayHasKey('message', $result['errors'][0]);
        $this->assertArrayHasKey('line', $result['errors'][0]);
        $this->assertSame(1, $result['errors'][0]['line']);
    }

    public function test_eval_of_request_input_is_syntax_valid_but_unsafe(): void
    {
        $result = Php_Snippet_Validator::validate("<?php eval(\$_POST['x']);");

        $this->assertTrue($result['syntax_valid']);
        $this->assertFalse($result['safe']);

        $messages = array_column($result['warnings'], 'message');
        $this->assertTrue(
            (bool) preg_grep('/eval/i', $messages),
            'Expected an eval-related warning, got: ' . implode(' | ', $messages)
        );
        $this->assertTrue(
            (bool) preg_grep('/request/i', $messages),
            'Expected a request-driven-execution warning, got: ' . implode(' | ', $messages)
        );
    }

    public function test_shell_exec_is_flagged(): void
    {
        $result = Php_Snippet_Validator::validate("<?php \$o = shell_exec('ls');");

        $this->assertTrue($result['syntax_valid']);
        $this->assertFalse($result['safe']);

        $messages = array_column($result['warnings'], 'message');
        $this->assertTrue(
            (bool) preg_grep('/shell_exec/i', $messages),
            'Expected a shell_exec warning, got: ' . implode(' | ', $messages)
        );
    }
}
