<?php

namespace WPMCP\Tests\Free\Code;

use WPMCP\Tools\Code\Validate_Php_Snippet;

class ValidatePhpSnippetTest extends \WP_UnitTestCase
{
    public function test_requires_code_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Validate_Php_Snippet())->handle([]);
    }

    public function test_delegates_to_the_validator(): void
    {
        $result = (new Validate_Php_Snippet())->handle(['code' => '<?php echo 1 + 1;']);

        $this->assertTrue($result['syntax_valid']);
        $this->assertTrue($result['safe']);
        $this->assertSame([], $result['errors']);
        $this->assertSame([], $result['warnings']);
    }

    public function test_reports_unsafe_snippet(): void
    {
        $result = (new Validate_Php_Snippet())->handle(['code' => "<?php \$o = shell_exec('ls');"]);

        $this->assertTrue($result['syntax_valid']);
        $this->assertFalse($result['safe']);
        $this->assertNotEmpty($result['warnings']);
    }
}
