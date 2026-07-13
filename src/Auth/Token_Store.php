<?php

namespace WPMCP\Auth;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Issues and validates OAuth 2.1 bearer access tokens. Backed by a single
 * wpmcp_oauth_tokens option, a map of a SHA-256 hash of the token to its
 * bound record: { client_id, user_id, scope, issued_at }.
 *
 * Token_Store::validate() is the helper the MCP permission layer
 * (Registrar's execute/permission_callback wiring) calls to authenticate a
 * caller-presented Bearer token to a WP user id and the client's granted
 * scope.
 *
 * Security properties:
 *  - the token is stored hashed, never plaintext (SHA-256, matching
 *    Client_Store/Code_Store: the token is already full-entropy random data
 *    looked up by exact value, not a human secret needing slow hashing), so
 *    a leaked options row cannot be replayed as a bearer token -- an
 *    attacker with read access to the stored hash still cannot present it as
 *    a valid Authorization header, because validate() hashes whatever is
 *    presented and looks up THAT, so presenting the raw hash just looks up
 *    hash(hash(token)), which was never stored;
 *  - tokens expire after TTL_SECONDS; validate() rejects (and evicts) an
 *    expired token. Unlike Code_Store's single-use codes, a valid
 *    unexpired token may be validated repeatedly (that is the point of a
 *    bearer access token).
 */
class Token_Store
{
    public const OPTION      = 'wpmcp_oauth_tokens';
    public const TTL_SECONDS = 3600;

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

    /** @return string The plaintext bearer token, returned exactly once. */
    public static function issue(string $client_id, int $user_id, string $scope): string
    {
        $token = 'at_' . bin2hex(random_bytes(32));

        $stored                       = self::load();
        $stored[ self::hash($token) ] = [
            'client_id'  => $client_id,
            'user_id'    => $user_id,
            'scope'      => $scope,
            'issued_at'  => self::now(),
        ];
        self::save($stored);

        return $token;
    }

    /**
     * Validate a presented bearer token. Returns its bound record
     * ({ client_id, user_id, scope }) if the token's hash matches a stored,
     * unexpired record, otherwise null. An expired match is also evicted.
     */
    public static function validate(string $token): ?array
    {
        $key    = self::hash($token);
        $stored = self::load();

        if (! isset($stored[ $key ])) {
            return null;
        }

        $record = $stored[ $key ];

        if (self::now() > $record['issued_at'] + self::TTL_SECONDS) {
            unset($stored[ $key ]);
            self::save($stored);
            return null;
        }

        return [
            'client_id' => $record['client_id'],
            'user_id'   => $record['user_id'],
            'scope'     => $record['scope'],
        ];
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
