<?php

namespace WPMCP\Tests\Free\Performance;

use WPMCP\Tools\Performance\Page_Audit;

class PageAuditSsrfTest extends \WP_UnitTestCase
{
    private Page_Audit $audit;
    private bool $http_request_fired = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->audit               = new Page_Audit();
        $this->http_request_fired  = false;
        add_filter('pre_http_request', [$this, 'record_and_fail'], 10, 3);
    }

    protected function tearDown(): void
    {
        remove_filter('pre_http_request', [$this, 'record_and_fail'], 10);
        parent::tearDown();
    }

    /**
     * If fetch() ever dispatches an HTTP request for a refused target, this
     * records that fact and fails the request, so a test can assert dispatch
     * never happened rather than merely getting a WP HTTP error back.
     */
    public function record_and_fail($preempt, $parsed_args, $url)
    {
        $this->http_request_fired = true;
        return new \WP_Error('unexpected_dispatch', 'fetch() should not have dispatched an HTTP request');
    }

    public function test_fetch_refuses_a_literal_loopback_url(): void
    {
        $result = $this->audit->fetch('http://127.0.0.1/');

        $this->assertFalse($this->http_request_fired, 'fetch() must refuse before dispatching HTTP');
        $this->assertFalse($result['ok']);
        $this->assertSame('refused_private_target', $result['error']);
    }

    public function test_fetch_refuses_a_private_range_ip_url(): void
    {
        $result = $this->audit->fetch('http://192.168.1.1/');

        $this->assertFalse($this->http_request_fired, 'fetch() must refuse before dispatching HTTP');
        $this->assertFalse($result['ok']);
        $this->assertSame('refused_private_target', $result['error']);
    }

    public function test_fetch_refuses_ipv6_loopback(): void
    {
        $result = $this->audit->fetch('http://[::1]/');

        $this->assertFalse($this->http_request_fired, 'fetch() must refuse before dispatching HTTP');
        $this->assertFalse($result['ok']);
        $this->assertSame('refused_private_target', $result['error']);
    }
}
