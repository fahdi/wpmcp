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
}
