<?php

namespace WPMCP\Auth;

use WPMCP\Governance\Governance_Audit_Log;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The authorization_code grant exchange (RFC 6749 4.1.3), PKCE-bound per RFC
 * 7636 (OAuth 2.1 makes this mandatory, S256-only -- see PKCE::verify()).
 * The REST token endpoint hands this the decoded request body and this does
 * everything the exchange step is responsible for: redeem the code (single-
 * use, short-lived -- Code_Store::consume() enforces both), verify the PKCE
 * verifier against the challenge the code was issued with, confirm the
 * presented client_id and redirect_uri match what the code was bound to, and
 * mint a bearer access token. Every outcome is recorded to the governance
 * audit log under ability 'oauth/token' with only the allow/deny outcome and
 * client_id, never the code, secret, or the minted token.
 *
 * Client authentication (client_id + client_secret) happens FIRST, before the
 * code is ever consumed: every client this plugin registers via DCR is
 * issued a secret (Client_Store::create() has no "public client" mode yet),
 * so client_secret is required and verified here, and a request that fails
 * client authentication must not burn the code (Code_Store::consume() is
 * single-use, so checking auth first preserves the code for a legitimate
 * retry). This uses the distinct RFC 6749 'invalid_client' error code, since
 * that failure is meaningfully different from an invalid/expired/mismatched
 * grant.
 *
 * Every OTHER rejection (bad/expired/replayed code, wrong redirect_uri,
 * wrong verifier) intentionally returns the single generic 'invalid_grant'
 * code rather than a more specific one per reason, so a caller probing the
 * endpoint (who has already authenticated as a real client) cannot use
 * varying error codes to distinguish "code doesn't exist" from "wrong
 * verifier" from "wrong redirect_uri" -- that distinction is exactly the
 * kind of oracle an attacker attempting to brute force or hijack a code
 * would want.
 */
class Token_Grant
{
    /**
     * @param array $params Decoded token request body.
     * @return array{access_token: string, token_type: string, expires_in: int, scope: string}|\WP_Error
     */
    public static function exchange(array $params): array|\WP_Error
    {
        $grant_type = (string) ($params['grant_type'] ?? '');
        if ('authorization_code' !== $grant_type) {
            return new \WP_Error(
                'unsupported_grant_type',
                'Only the authorization_code grant type is supported.'
            );
        }

        $client_id     = (string) ($params['client_id'] ?? '');
        $client_secret = (string) ($params['client_secret'] ?? '');
        if (! Client_Store::verify_secret($client_id, $client_secret)) {
            self::audit(false, $client_id);
            return new \WP_Error('invalid_client', 'Client authentication failed.');
        }

        $code = (string) ($params['code'] ?? '');
        if ('' === $code) {
            return self::deny($client_id);
        }

        $record = Code_Store::consume($code);
        if (null === $record) {
            return self::deny($client_id);
        }

        if ($client_id !== $record['client_id']) {
            return self::deny($client_id);
        }

        $redirect_uri = (string) ($params['redirect_uri'] ?? '');
        if ($redirect_uri !== $record['redirect_uri']) {
            return self::deny($client_id);
        }

        $verifier = (string) ($params['code_verifier'] ?? '');
        if (! PKCE::verify($verifier, $record['code_challenge'], $record['code_challenge_method'])) {
            return self::deny($client_id);
        }

        $token = Token_Store::issue($client_id, (int) $record['user_id'], (string) $record['scope']);

        self::audit(true, $client_id);

        return [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => Token_Store::TTL_SECONDS,
            'scope'        => $record['scope'],
        ];
    }

    private static function deny(string $client_id): \WP_Error
    {
        self::audit(false, $client_id);

        return new \WP_Error('invalid_grant', 'The provided authorization grant is invalid, expired, or does not match.');
    }

    private static function audit(bool $allowed, string $client_id): void
    {
        try {
            Governance_Audit_Log::record('oauth/token', 'client:' . $client_id, $allowed);
        } catch (\Throwable $e) {
            // Auditing must never break the token exchange outcome it is observing.
        }
    }
}
