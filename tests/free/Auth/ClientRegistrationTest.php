<?php

namespace WPMCP\Tests\Free\Auth;

use WPMCP\Auth\Client_Registration;
use WPMCP\Auth\Client_Store;
use WPMCP\Governance\Governance_Audit_Log;

/**
 * Client_Registration is the RFC 7591 DCR request handler: validates
 * redirect_uris, enforces a dedicated registration rate limit (distinct from
 * the general per-ability budget, since registration happens before any
 * client identity exists), enforces Client_Store's total-client cap, and
 * records every outcome to the governance audit log without ever including
 * the plaintext secret.
 */
class ClientRegistrationTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        delete_option(Client_Store::OPTION);
        delete_option(Governance_Audit_Log::OPTION);
        Client_Registration::set_clock_override(fn() => 1_700_000_000);
    }

    protected function tearDown(): void
    {
        delete_option(Client_Store::OPTION);
        delete_option(Governance_Audit_Log::OPTION);
        Client_Registration::set_clock_override(null);
        remove_all_filters('wpmcp_oauth_registration_rate_limit');
        remove_all_filters('wpmcp_oauth_max_clients');
        parent::tearDown();
    }

    public function test_valid_registration_returns_client_id_and_secret(): void
    {
        $result = Client_Registration::register([
            'client_name'   => 'Example App',
            'redirect_uris' => ['https://example.com/cb'],
        ], 'ip:203.0.113.1');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('client_id', $result);
        $this->assertArrayHasKey('client_secret', $result);
    }

    public function test_valid_registration_is_audited_without_the_secret(): void
    {
        Client_Registration::register([
            'client_name'   => 'Example App',
            'redirect_uris' => ['https://example.com/cb'],
        ], 'ip:203.0.113.1');

        $entries = Governance_Audit_Log::list();
        $this->assertCount(1, $entries);
        $this->assertSame('oauth/register', $entries[0]['ability']);
        $this->assertTrue($entries[0]['allowed']);

        $serialized = wp_json_encode($entries);
        $this->assertStringNotContainsString('secret_', $serialized);
    }

    public function test_missing_redirect_uris_is_rejected(): void
    {
        $result = Client_Registration::register([
            'client_name'   => 'No Redirects',
            'redirect_uris' => [],
        ], 'ip:203.0.113.2');

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('invalid_redirect_uri', $result->get_error_code());
    }

    public function test_invalid_redirect_uri_is_rejected(): void
    {
        $result = Client_Registration::register([
            'client_name'   => 'Bad Redirect',
            'redirect_uris' => ['http://example.com/cb'],
        ], 'ip:203.0.113.3');

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('invalid_redirect_uri', $result->get_error_code());
    }

    public function test_a_rejected_registration_is_audited_as_denied(): void
    {
        Client_Registration::register([
            'client_name'   => 'Bad Redirect',
            'redirect_uris' => ['http://example.com/cb'],
        ], 'ip:203.0.113.4');

        $entries = Governance_Audit_Log::list();
        $this->assertCount(1, $entries);
        $this->assertSame('oauth/register', $entries[0]['ability']);
        $this->assertFalse($entries[0]['allowed']);
    }

    public function test_registration_is_rate_limited_per_client_key(): void
    {
        add_filter('wpmcp_oauth_registration_rate_limit', fn() => 2);

        $args = ['client_name' => 'App', 'redirect_uris' => ['https://example.com/cb']];

        $this->assertIsArray(Client_Registration::register($args, 'ip:203.0.113.5'));
        $this->assertIsArray(Client_Registration::register($args, 'ip:203.0.113.5'));

        $third = Client_Registration::register($args, 'ip:203.0.113.5');

        $this->assertInstanceOf(\WP_Error::class, $third);
        $this->assertSame('registration_rate_limited', $third->get_error_code());
    }

    public function test_rate_limit_is_independent_per_client_key(): void
    {
        add_filter('wpmcp_oauth_registration_rate_limit', fn() => 1);

        $args = ['client_name' => 'App', 'redirect_uris' => ['https://example.com/cb']];

        $this->assertIsArray(Client_Registration::register($args, 'ip:203.0.113.6'));
        // A different client key must still have its own fresh budget.
        $this->assertIsArray(Client_Registration::register($args, 'ip:203.0.113.7'));
    }

    public function test_registration_is_rejected_once_the_client_cap_is_reached(): void
    {
        add_filter('wpmcp_oauth_max_clients', fn() => 1);

        $args = ['client_name' => 'App', 'redirect_uris' => ['https://example.com/cb']];

        $this->assertIsArray(Client_Registration::register($args, 'ip:203.0.113.8'));

        $second = Client_Registration::register($args, 'ip:203.0.113.9');

        $this->assertInstanceOf(\WP_Error::class, $second);
        $this->assertSame('client_cap_reached', $second->get_error_code());
    }
}
