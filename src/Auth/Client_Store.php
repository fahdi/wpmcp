<?php

namespace WPMCP\Auth;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * RFC 7591 Dynamic Client Registration record store: a single
 * wpmcp_oauth_clients option, a map of client_id => record.
 *
 * Security properties:
 *  - client_secret is generated with random_bytes() (full entropy, nothing
 *    user-supplied) and only its SHA-256 hash is ever persisted; the
 *    plaintext is returned once, at creation time, and never again. A leaked
 *    option row therefore cannot be replayed as a valid secret. SHA-256 (not
 *    a slow password hash like bcrypt) is the appropriate primitive here
 *    because the secret is already high-entropy random data being looked up
 *    by exact value, not a human-chosen password being brute-force-resisted.
 *  - total registered clients is capped at MAX_CLIENTS so a registration
 *    flood (even one that gets past Client_Registration's rate limit) cannot
 *    grow the store without bound.
 *
 * A record is { client_id, client_secret_hash, client_name, redirect_uris,
 * created_at }.
 */
class Client_Store
{
    public const OPTION = 'wpmcp_oauth_clients';

    /** Hard cap on total registered clients, filterable via wpmcp_oauth_max_clients. */
    public const MAX_CLIENTS = 100;

    private static function max_clients(): int
    {
        return (int) apply_filters('wpmcp_oauth_max_clients', self::MAX_CLIENTS);
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

    public static function count(): int
    {
        return count(self::load());
    }

    /**
     * Register a new client. $client_name and $redirect_uris are arrays only
     * because callers currently pass single-item collections in tests; the
     * public registration entry point (Client_Registration) is responsible
     * for shaping/validating real request input before calling this.
     *
     * @param string[] $client_name  Human-readable name (first element used).
     * @param string[] $redirect_uris Absolute redirect URIs already validated
     *                                by the caller.
     * @return array{client_id: string, client_secret: string} The plaintext
     *               secret, returned exactly once.
     *
     * @throws \RuntimeException When the client cap (MAX_CLIENTS) is reached.
     */
    public static function create(array $client_name, array $redirect_uris): array
    {
        $stored = self::load();

        if (count($stored) >= self::max_clients()) {
            throw new \RuntimeException('OAuth client registration cap reached.');
        }

        $client_id     = self::generate_token('client_');
        $client_secret = self::generate_token('secret_');

        $stored[ $client_id ] = [
            'client_id'          => $client_id,
            'client_secret_hash' => self::hash($client_secret),
            'client_name'        => $client_name[0] ?? '',
            'redirect_uris'      => array_values($redirect_uris),
            'created_at'         => time(),
        ];

        self::save($stored);

        return [
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        ];
    }

    /** Fetch a client's stored record (never includes the plaintext secret), or null. */
    public static function get(string $client_id): ?array
    {
        $stored = self::load();
        return $stored[ $client_id ] ?? null;
    }

    /** Whether $client_secret is the correct plaintext secret for $client_id. */
    public static function verify_secret(string $client_id, string $client_secret): bool
    {
        $record = self::get($client_id);
        if (null === $record) {
            return false;
        }

        return hash_equals($record['client_secret_hash'], self::hash($client_secret));
    }

    private static function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    private static function generate_token(string $prefix): string
    {
        return $prefix . bin2hex(random_bytes(24));
    }
}
