<?php

namespace WPMCP\Tests\Free\Auth;

use WPMCP\Auth\Code_Store;

/**
 * Code_Store issues and redeems OAuth 2.1 authorization codes. Two security
 * properties are covered here:
 *  - a code is single-use: consume() returns the bound record exactly once,
 *    and a replayed consume() (or lookup after consumption) must fail;
 *  - a code is short-lived: consume() rejects an expired code even though it
 *    was never consumed.
 * The code itself is stored hashed (mirroring Client_Store's secret
 * handling) so a leaked options row cannot be replayed as a valid code.
 */
class CodeStoreTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        delete_option(Code_Store::OPTION);
        Code_Store::set_clock_override(null);
    }

    protected function tearDown(): void
    {
        delete_option(Code_Store::OPTION);
        Code_Store::set_clock_override(null);
        parent::tearDown();
    }

    private function issue(): string
    {
        return Code_Store::issue([
            'client_id'            => 'client_abc',
            'user_id'              => 42,
            'redirect_uri'         => 'https://example.com/cb',
            'code_challenge'       => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            'code_challenge_method' => 'S256',
            'scope'                => 'read',
        ]);
    }

    public function test_issue_returns_a_non_empty_code(): void
    {
        $code = $this->issue();

        $this->assertIsString($code);
        $this->assertNotSame('', $code);
    }

    public function test_stored_record_never_contains_the_plaintext_code(): void
    {
        $code = $this->issue();

        $stored = get_option(Code_Store::OPTION);
        $this->assertIsArray($stored);

        $serialized = wp_json_encode($stored);
        $this->assertStringNotContainsString($code, $serialized);
    }

    public function test_consume_returns_the_bound_record_for_a_valid_code(): void
    {
        $code   = $this->issue();
        $record = Code_Store::consume($code);

        $this->assertIsArray($record);
        $this->assertSame('client_abc', $record['client_id']);
        $this->assertSame(42, $record['user_id']);
        $this->assertSame('https://example.com/cb', $record['redirect_uri']);
        $this->assertSame('E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM', $record['code_challenge']);
        $this->assertSame('S256', $record['code_challenge_method']);
        $this->assertSame('read', $record['scope']);
    }

    public function test_consume_is_single_use_a_replay_is_rejected(): void
    {
        $code = $this->issue();

        $this->assertIsArray(Code_Store::consume($code));
        $this->assertNull(Code_Store::consume($code));
    }

    public function test_consume_rejects_an_unknown_code(): void
    {
        $this->assertNull(Code_Store::consume('never-issued'));
    }

    public function test_consume_rejects_an_expired_code(): void
    {
        Code_Store::set_clock_override(fn() => 1000);
        $code = $this->issue();

        // Advance past the TTL.
        Code_Store::set_clock_override(fn() => 1000 + Code_Store::TTL_SECONDS + 1);

        $this->assertNull(Code_Store::consume($code));
    }

    public function test_consume_accepts_a_code_right_before_expiry(): void
    {
        Code_Store::set_clock_override(fn() => 1000);
        $code = $this->issue();

        Code_Store::set_clock_override(fn() => 1000 + Code_Store::TTL_SECONDS);

        $this->assertIsArray(Code_Store::consume($code));
    }
}
