<?php

namespace WPMCP\Tests\Free\Auth;

use WPMCP\Auth\Redirect_Uri_Validator;

/**
 * OAuth 2.1 requires redirect_uris to be absolute and, except for loopback
 * addresses used by native/CLI clients, HTTPS. This is the gate DCR
 * (Client_Registration) runs every submitted redirect_uri through before a
 * client is ever created; rejecting an unsafe URI here is what stops a
 * malicious registration from later hijacking an authorization code via an
 * attacker-controlled redirect target.
 */
class RedirectUriValidatorTest extends \WP_UnitTestCase
{
    public function test_https_uri_is_valid(): void
    {
        $this->assertTrue(Redirect_Uri_Validator::is_valid('https://example.com/callback'));
    }

    public function test_http_loopback_127_0_0_1_is_valid(): void
    {
        $this->assertTrue(Redirect_Uri_Validator::is_valid('http://127.0.0.1:8080/callback'));
    }

    public function test_http_loopback_localhost_is_valid(): void
    {
        $this->assertTrue(Redirect_Uri_Validator::is_valid('http://localhost:9000/cb'));
    }

    public function test_http_loopback_ipv6_is_valid(): void
    {
        $this->assertTrue(Redirect_Uri_Validator::is_valid('http://[::1]:8080/cb'));
    }

    public function test_plain_http_non_loopback_is_rejected(): void
    {
        $this->assertFalse(Redirect_Uri_Validator::is_valid('http://example.com/callback'));
    }

    public function test_relative_uri_is_rejected(): void
    {
        $this->assertFalse(Redirect_Uri_Validator::is_valid('/callback'));
    }

    public function test_empty_string_is_rejected(): void
    {
        $this->assertFalse(Redirect_Uri_Validator::is_valid(''));
    }

    public function test_uri_with_fragment_is_rejected(): void
    {
        // RFC 6749 3.1.2: redirection endpoint URI MUST NOT include a fragment.
        $this->assertFalse(Redirect_Uri_Validator::is_valid('https://example.com/callback#frag'));
    }

    public function test_javascript_scheme_is_rejected(): void
    {
        $this->assertFalse(Redirect_Uri_Validator::is_valid('javascript:alert(1)'));
    }

    public function test_custom_scheme_is_accepted_for_native_apps(): void
    {
        // Non-http(s) custom schemes (native app redirect) are allowed as long
        // as they are absolute; only http without loopback is disallowed.
        $this->assertTrue(Redirect_Uri_Validator::is_valid('com.example.app:/callback'));
    }
}
