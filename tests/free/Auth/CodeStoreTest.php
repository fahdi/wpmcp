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

    /**
     * consume() must be redeemable at most once even across many repeated
     * attempts against the same code (issue #43 C3). PHPUnit runs
     * single-threaded, so this cannot reproduce the original TOCTOU
     * interleave (two requests both reading the option before either
     * writes) deterministically -- that requires genuine concurrent
     * requests. What this test proves instead: hammering consume() with
     * the same code repeatedly yields the bound record exactly once and
     * null every other time, i.e. the claim is idempotent-safe under
     * repetition, not just under a single sequential replay. See
     * Code_Store::consume()'s doc comment for why the underlying $wpdb
     * compare-and-swap closes the race a plain get_option/update_option
     * pair could not.
     */
    public function test_consume_is_redeemable_at_most_once_under_repeated_attempts(): void
    {
        $code = $this->issue();

        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = Code_Store::consume($code);
        }

        $successes = array_filter($results, static fn($r) => null !== $r);
        $this->assertCount(1, $successes, 'Exactly one consume() call may claim the code.');
    }

    /**
     * White-box proof that consume() detects and rejects a stale write
     * attempt, which is the exact TOCTOU the original load -> unset -> save
     * implementation was vulnerable to (issue #43 C3). The `option_{name}`
     * filter fires on every get_option() call, so it is used here to inject
     * a "concurrent" mutation of the real stored option in between
     * consume()'s own read and its compare-and-swap write attempt --
     * simulating a second request that raced ahead and already consumed
     * the same code. The original implementation would have blindly
     * overwritten that concurrent change (both callers would have "won").
     * The fixed implementation's compare-and-swap must detect the row no
     * longer matches what it read and refuse to claim the code, instead of
     * silently overwriting the concurrent consumer's change.
     */
    public function test_consume_detects_and_rejects_a_stale_concurrent_write(): void
    {
        $code = $this->issue();
        $key  = array_key_first(get_option(Code_Store::OPTION));

        $armed = false;
        $filter = function ($value) use (&$armed, $key) {
            if (! $armed) {
                $armed = true;
                // Simulate a concurrent request that already consumed this
                // exact code between our read and our write attempt.
                $concurrent = $value;
                unset($concurrent[ $key ]);
                update_option(Code_Store::OPTION, $concurrent);
            }
            return $value;
        };
        add_filter('option_' . Code_Store::OPTION, $filter);

        $result = Code_Store::consume($code);

        remove_filter('option_' . Code_Store::OPTION, $filter);

        // The concurrent writer already removed the record, so this
        // caller's stale read must not resurrect it: it must lose the race
        // cleanly (null), never returning the record a second time.
        $this->assertNull($result);

        $stored = get_option(Code_Store::OPTION);
        $this->assertArrayNotHasKey($key, $stored, 'The code must remain consumed, not resurrected by a stale overwrite.');
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
