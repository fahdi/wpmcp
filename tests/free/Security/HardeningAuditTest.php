<?php

namespace WPMCP\Tests\Free\Security;

use WPMCP\Tools\Security\Hardening_Audit;

class HardeningAuditTest extends \WP_UnitTestCase
{
    private function audit(): Hardening_Audit
    {
        return new Hardening_Audit();
    }

    public function test_file_edit_disabled_passes_enabled_warns(): void
    {
        $this->assertSame('pass', $this->audit()->evaluate_file_edit(true)['status']);
        $this->assertSame('warning', $this->audit()->evaluate_file_edit(false)['status']);
    }

    public function test_debug_display_on_in_production_warns(): void
    {
        $this->assertSame('warning', $this->audit()->evaluate_debug_display(true, 'production')['status']);
        $this->assertSame('info', $this->audit()->evaluate_debug_display(true, 'local')['status']);
        $this->assertSame('pass', $this->audit()->evaluate_debug_display(false, 'production')['status']);
    }

    public function test_admin_username_present_warns(): void
    {
        $this->assertSame('warning', $this->audit()->evaluate_admin_user(true)['status']);
        $this->assertSame('pass', $this->audit()->evaluate_admin_user(false)['status']);
    }

    public function test_xmlrpc_enabled_warns(): void
    {
        $this->assertSame('warning', $this->audit()->evaluate_xmlrpc(true)['status']);
        $this->assertSame('pass', $this->audit()->evaluate_xmlrpc(false)['status']);
    }

    public function test_version_disclosure_via_readme_or_generator_warns(): void
    {
        $this->assertSame('warning', $this->audit()->evaluate_version_disclosure(true, false)['status']);
        $this->assertSame('warning', $this->audit()->evaluate_version_disclosure(false, true)['status']);
        $this->assertSame('pass', $this->audit()->evaluate_version_disclosure(false, false)['status']);
    }

    public function test_non_https_home_warns(): void
    {
        $this->assertSame('warning', $this->audit()->evaluate_https('http://example.com')['status']);
        $this->assertSame('pass', $this->audit()->evaluate_https('https://example.com')['status']);
    }

    public function test_security_headers_missing_warns(): void
    {
        $present = [
            'x-frame-options'           => 'SAMEORIGIN',
            'x-content-type-options'    => 'nosniff',
            'strict-transport-security' => 'max-age=31536000',
            'content-security-policy'   => "default-src 'self'",
        ];

        $this->assertSame('pass', $this->audit()->evaluate_security_headers($present)['status']);
        $this->assertSame('warning', $this->audit()->evaluate_security_headers([])['status']);
    }

    public function test_run_flags_missing_headers_from_a_mocked_home_response(): void
    {
        // Serve a canned header-less home page so run() sees no security headers
        // and no generator meta, without touching the real network.
        $serve = static function () {
            return [
                'headers'  => [],
                'body'     => '<html><head></head><body>hi</body></html>',
                'response' => ['code' => 200, 'message' => 'OK'],
                'cookies'  => [],
                'filename' => null,
            ];
        };
        add_filter('pre_http_request', $serve, 10, 3);

        $result = (new Hardening_Audit())->run();

        remove_filter('pre_http_request', $serve, 10);

        $this->assertTrue($result['headers_fetch']['ok']);
        $ids = array_map(static fn($finding) => $finding['id'], $result['findings']);
        $this->assertContains('harden_security_headers', $ids);
        $this->assertContains('harden_file_edit', $ids);
    }

    public function test_home_fetch_targets_the_site_host_over_the_safe_client(): void
    {
        // SSRF-safe-fetch guard: run()'s single loopback GET must target the
        // site's own home URL via wp_safe_remote_get(), never an attacker host.
        $seen    = [];
        $capture = static function ($preempt, $args, $url) use (&$seen) {
            $seen[] = $url;
            return new \WP_Error('captured', 'short-circuited in test');
        };
        add_filter('pre_http_request', $capture, 10, 3);

        (new Hardening_Audit())->run();

        remove_filter('pre_http_request', $capture, 10);

        $home_host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
        $this->assertNotEmpty($seen);
        foreach ($seen as $url) {
            $this->assertSame($home_host, (string) wp_parse_url($url, PHP_URL_HOST));
        }
    }
}
