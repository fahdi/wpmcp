<?php

namespace WPMCP\Tests\Free\Auth;

use WPMCP\Auth\Client_Store;

/**
 * Client_Store is the RFC 7591 Dynamic Client Registration record store: a
 * single wpmcp_oauth_clients option keyed by client_id. Two properties are
 * security-load-bearing and covered here:
 *  - the client_secret is NEVER stored in plaintext, only its hash, so a
 *    leaked option row cannot be replayed as a valid secret;
 *  - total registered clients is capped (Client_Store::MAX_CLIENTS), so an
 *    attacker cannot flood the store with unbounded registrations even if
 *    they get past the rate limiter.
 */
class ClientStoreTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        delete_option(Client_Store::OPTION);
    }

    protected function tearDown(): void
    {
        delete_option(Client_Store::OPTION);
        parent::tearDown();
    }

    public function test_create_stores_a_client_and_returns_the_plaintext_secret_once(): void
    {
        $result = Client_Store::create(['Example App'], ['https://example.com/cb']);

        $this->assertArrayHasKey('client_id', $result);
        $this->assertArrayHasKey('client_secret', $result);
        $this->assertNotEmpty($result['client_id']);
        $this->assertNotEmpty($result['client_secret']);
    }

    public function test_stored_record_never_contains_the_plaintext_secret(): void
    {
        $result = Client_Store::create(['Example App'], ['https://example.com/cb']);

        $stored = get_option(Client_Store::OPTION);

        $this->assertArrayHasKey($result['client_id'], $stored);
        $this->assertArrayNotHasKey('client_secret', $stored[ $result['client_id'] ]);
        $this->assertArrayHasKey('client_secret_hash', $stored[ $result['client_id'] ]);
        $this->assertNotSame($result['client_secret'], $stored[ $result['client_id'] ]['client_secret_hash']);
    }

    public function test_verify_secret_accepts_the_correct_plaintext_secret(): void
    {
        $result = Client_Store::create(['Example App'], ['https://example.com/cb']);

        $this->assertTrue(Client_Store::verify_secret($result['client_id'], $result['client_secret']));
    }

    public function test_verify_secret_rejects_a_wrong_secret(): void
    {
        $result = Client_Store::create(['Example App'], ['https://example.com/cb']);

        $this->assertFalse(Client_Store::verify_secret($result['client_id'], 'totally-wrong-secret'));
    }

    public function test_verify_secret_rejects_an_unknown_client_id(): void
    {
        $this->assertFalse(Client_Store::verify_secret('no-such-client', 'anything'));
    }

    public function test_get_returns_the_stored_record(): void
    {
        $result = Client_Store::create(['Example App'], ['https://example.com/cb']);

        $record = Client_Store::get($result['client_id']);

        $this->assertSame($result['client_id'], $record['client_id']);
        $this->assertSame(['https://example.com/cb'], $record['redirect_uris']);
    }

    public function test_get_returns_null_for_an_unknown_client(): void
    {
        $this->assertNull(Client_Store::get('no-such-client'));
    }

    public function test_count_reflects_the_number_of_registered_clients(): void
    {
        $this->assertSame(0, Client_Store::count());

        Client_Store::create(['A'], ['https://a.example.com/cb']);
        Client_Store::create(['B'], ['https://b.example.com/cb']);

        $this->assertSame(2, Client_Store::count());
    }

    public function test_create_throws_once_the_client_cap_is_reached(): void
    {
        for ($i = 0; $i < Client_Store::MAX_CLIENTS; $i++) {
            Client_Store::create(["Client {$i}"], ["https://example.com/cb{$i}"]);
        }

        $this->assertSame(Client_Store::MAX_CLIENTS, Client_Store::count());

        $this->expectException(\RuntimeException::class);
        Client_Store::create(['One Too Many'], ['https://example.com/cb-overflow']);
    }
}
