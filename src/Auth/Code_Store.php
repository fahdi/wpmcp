<?php

namespace WPMCP\Auth;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Issues and redeems OAuth 2.1 authorization codes (RFC 6749 4.1, PKCE-bound
 * per RFC 7636). Backed by a single wpmcp_oauth_codes option, a map of a
 * SHA-256 hash of the code to its bound record: { client_id, user_id,
 * redirect_uri, code_challenge, code_challenge_method, scope, issued_at }.
 *
 * Security properties:
 *  - the code itself is never stored in plaintext (only its hash), matching
 *    Client_Store's secret handling, so a leaked option row cannot be
 *    replayed as a valid code;
 *  - consume() is single-use: it atomically removes the record as part of
 *    returning it, so a second consume() with the same code (a replay) gets
 *    null, never the record twice;
 *  - codes expire after TTL_SECONDS (short-lived, per OAuth 2.1 guidance);
 *    consume() rejects an expired code even if it was never consumed, and
 *    also evicts it so expired entries do not linger in the option forever.
 */
class Code_Store
{
    public const OPTION      = 'wpmcp_oauth_codes';
    public const TTL_SECONDS = 60;

    private static $clock_override = null;

    public static function set_clock_override(?callable $clock): void
    {
        self::$clock_override = $clock;
    }

    private static function now(): int
    {
        return null !== self::$clock_override ? (int) (self::$clock_override)() : time();
    }

    private static function load(): array
    {
        $stored = get_option(self::OPTION, []);
        return is_array($stored) ? $stored : [];
    }

    private static function save(array $stored): void
    {
        update_option(self::OPTION, $stored);
    }

    /**
     * @param array $fields client_id, user_id, redirect_uri, code_challenge,
     *                      code_challenge_method, scope.
     * @return string The plaintext code, returned exactly once.
     */
    public static function issue(array $fields): string
    {
        $code = 'ac_' . bin2hex(random_bytes(32));

        $stored              = self::load();
        $stored[ self::hash($code) ] = [
            'client_id'             => (string) ($fields['client_id'] ?? ''),
            'user_id'               => (int) ($fields['user_id'] ?? 0),
            'redirect_uri'          => (string) ($fields['redirect_uri'] ?? ''),
            'code_challenge'        => (string) ($fields['code_challenge'] ?? ''),
            'code_challenge_method' => (string) ($fields['code_challenge_method'] ?? ''),
            'scope'                 => (string) ($fields['scope'] ?? ''),
            'issued_at'             => self::now(),
        ];
        self::save($stored);

        return $code;
    }

    /**
     * Redeem $code: returns its bound record and removes it (single-use) if
     * it exists and has not expired, otherwise returns null. An expired
     * match is also evicted so it cannot be found again.
     */
    public static function consume(string $code): ?array
    {
        $key    = self::hash($code);
        $stored = self::load();

        if (! isset($stored[ $key ])) {
            return null;
        }

        $record = $stored[ $key ];
        unset($stored[ $key ]);
        self::save($stored);

        if (self::now() > $record['issued_at'] + self::TTL_SECONDS) {
            return null;
        }

        return $record;
    }

    private static function hash(string $code): string
    {
        return hash('sha256', $code);
    }
}
