<?php

namespace WPMCP\Tests\Free\Diagnostics;

use WPMCP\Tools\Diagnostics\Get_Debug_Log;

class GetDebugLogTest extends \WP_UnitTestCase
{
    private string $log_path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->log_path = WP_CONTENT_DIR . '/debug.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->log_path)) {
            unlink($this->log_path);
        }
        parent::tearDown();
    }

    public function test_returns_empty_result_when_no_log_exists(): void
    {
        $out = (new Get_Debug_Log())->handle([]);

        $this->assertStringEndsWith('/wp-content/debug.log', $out['path']);
        $this->assertFalse($out['exists']);
        $this->assertSame('', $out['content']);
    }

    public function test_returns_the_full_content_when_under_the_cap(): void
    {
        file_put_contents($this->log_path, "line one\nline two\nline three\n");

        $out = (new Get_Debug_Log())->handle([]);

        $this->assertTrue($out['exists']);
        $this->assertSame("line one\nline two\nline three\n", $out['content']);
    }

    public function test_bounds_the_result_to_the_last_n_lines(): void
    {
        $lines = [];
        for ($i = 1; $i <= 500; $i++) {
            $lines[] = "log line {$i}";
        }
        file_put_contents($this->log_path, implode("\n", $lines) . "\n");

        $out = (new Get_Debug_Log())->handle(['lines' => 200]);

        $out_lines = explode("\n", rtrim($out['content'], "\n"));
        $this->assertCount(200, $out_lines);
        $this->assertSame('log line 301', $out_lines[0]);
        $this->assertSame('log line 500', $out_lines[199]);
    }

    public function test_bounds_the_result_to_a_kilobyte_cap(): void
    {
        // A single line far larger than the 64KB cap, so byte-capping (not
        // just line-capping) must kick in.
        file_put_contents($this->log_path, str_repeat('x', 200 * 1024));

        $out = (new Get_Debug_Log())->handle([]);

        $this->assertLessThanOrEqual(64 * 1024, strlen($out['content']));
    }

    public function test_refuses_a_path_traversal_attempt(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Get_Debug_Log())->handle(['path' => '/etc/passwd']);
    }

    public function test_refuses_a_relative_traversal_attempt(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Get_Debug_Log())->handle(['path' => WP_CONTENT_DIR . '/../../../etc/passwd']);
    }
}
