<?php

namespace WPMCP\Tests\Free\Performance;

use WPMCP\Tools\Performance\Page_Audit;

class PageAuditTest extends \WP_UnitTestCase
{
    private Page_Audit $audit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->audit = new Page_Audit();
    }

    private function fetched(string $body, array $headers = [], int $status = 200): array
    {
        return [
            'ok'          => true,
            'status_code' => $status,
            'response_ms' => 120,
            'total_bytes' => strlen($body),
            'headers'     => $headers,
            'body'        => $body,
            'error'       => null,
            'host'        => 'example.com',
        ];
    }

    private function status_of(array $result, string $id): string
    {
        foreach ($result['findings'] as $finding) {
            if ($finding['id'] === $id) {
                return $finding['status'];
            }
        }
        return 'MISSING';
    }

    public function test_failed_fetch_degrades_gracefully(): void
    {
        $result = $this->audit->analyze([
            'ok' => false, 'status_code' => 0, 'response_ms' => 0, 'total_bytes' => 0,
            'headers' => [], 'body' => '', 'error' => 'timeout', 'host' => 'example.com',
        ], false);

        $this->assertFalse($result['page_fetch']['ok']);
        $this->assertSame('timeout', $result['page_fetch']['error']);
        $this->assertSame('warning', $this->status_of($result, 'page_fetch'));
        $this->assertCount(1, $result['findings']);
    }

    public function test_http_status_pass_on_200_warning_otherwise(): void
    {
        $pass = $this->audit->analyze($this->fetched('<html></html>', [], 200), false);
        $this->assertSame('pass', $this->status_of($pass, 'http_status'));

        $warn = $this->audit->analyze($this->fetched('<html></html>', [], 404), false);
        $this->assertSame('warning', $this->status_of($warn, 'http_status'));
    }

    private function fetched_with_ms(string $body, int $ms): array
    {
        $fetched               = $this->fetched($body);
        $fetched['response_ms'] = $ms;
        return $fetched;
    }

    public function test_response_time_pass_at_or_under_800ms_warning_above(): void
    {
        $this->assertSame('pass', $this->status_of($this->audit->analyze($this->fetched_with_ms('<html></html>', 800), false), 'response_time'));
        $this->assertSame('warning', $this->status_of($this->audit->analyze($this->fetched_with_ms('<html></html>', 801), false), 'response_time'));
    }
}
