<?php

namespace WPMCP\Tests\Free\Security;

use WPMCP\Tools\Security\Integrity_Audit;

class IntegrityAuditTest extends \WP_UnitTestCase
{
    private function statuses(array $findings): array
    {
        $out = [];
        foreach ($findings as $finding) {
            $out[ $finding['value'] ] = $finding['status'];
        }
        return $out;
    }

    public function test_matching_files_produce_no_findings(): void
    {
        $checksums = ['wp-load.php' => 'aaa', 'index.php' => 'bbb'];
        $hasher    = static fn($path) => ['wp-load.php' => 'aaa', 'index.php' => 'bbb'][ $path ] ?? null;

        $this->assertSame([], (new Integrity_Audit())->diff($checksums, $hasher));
    }

    public function test_modified_file_is_critical_and_missing_is_warning(): void
    {
        $checksums = ['wp-load.php' => 'aaa', 'gone.php' => 'bbb'];
        $hasher    = static fn($path) => 'wp-load.php' === $path ? 'CHANGED' : null;

        $map = $this->statuses((new Integrity_Audit())->diff($checksums, $hasher));

        $this->assertSame('critical', $map['wp-load.php']);
        $this->assertSame('warning', $map['gone.php']);
    }

    public function test_checksum_comparison_is_case_insensitive(): void
    {
        $checksums = ['wp-load.php' => 'ABCDEF'];
        $hasher    = static fn($path) => 'abcdef';

        $this->assertSame([], (new Integrity_Audit())->diff($checksums, $hasher));
    }

    public function test_run_degrades_gracefully_when_the_checksum_api_is_unreachable(): void
    {
        // Force every outbound HTTP request to fail: get_core_checksums() then
        // returns false and run() must degrade to an informational finding
        // rather than throwing or reporting false positives. No real network.
        $fail = static fn() => new \WP_Error('offline', 'no network in tests');
        add_filter('pre_http_request', $fail, 10, 3);

        $result = (new Integrity_Audit())->run();

        remove_filter('pre_http_request', $fail, 10);

        $this->assertFalse($result['api']['ok']);
        $ids = array_map(static fn($finding) => $finding['id'], $result['findings']);
        $this->assertContains('integrity_unavailable', $ids);
    }

    public function test_checksum_fetch_targets_only_the_public_wordpress_org_api(): void
    {
        // SSRF-safe-fetch guard: the only outbound request this audit makes must
        // go to the public wordpress.org checksum API over HTTPS, dispatched via
        // wp_safe_remote_get(). We capture the URL through pre_http_request and
        // assert it never points at an internal or attacker-chosen host. The
        // request is short-circuited here, so no real network is touched.
        $seen = [];
        $capture = static function ($preempt, $args, $url) use (&$seen) {
            $seen[] = $url;
            return new \WP_Error('captured', 'short-circuited in test');
        };
        add_filter('pre_http_request', $capture, 10, 3);

        (new Integrity_Audit())->run();

        remove_filter('pre_http_request', $capture, 10);

        $this->assertNotEmpty($seen, 'run() must dispatch a checksum request.');
        foreach ($seen as $url) {
            $host = (string) wp_parse_url($url, PHP_URL_HOST);
            $this->assertSame('api.wordpress.org', $host);
            $this->assertSame('https', strtolower((string) wp_parse_url($url, PHP_URL_SCHEME)));
        }
    }
}
