<?php

namespace WPMCP\Auth;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Validates a redirect_uri submitted to Dynamic Client Registration (RFC
 * 7591) against OAuth 2.1's rules: the URI must be absolute, must not carry a
 * fragment (RFC 6749 3.1.2), and plain 'http' is only acceptable for a
 * loopback host (127.0.0.1, ::1, or localhost) so native/CLI clients using an
 * ephemeral local redirect still work; every other scheme+host combination
 * must be 'https' (or a non-http custom scheme for native app redirects,
 * which OAuth 2.1 also permits since it is not interceptable the way plain
 * HTTP over a network is).
 */
class Redirect_Uri_Validator
{
    private const LOOPBACK_HOSTS = ['127.0.0.1', '::1', 'localhost'];

    /**
     * Schemes that are never acceptable as a redirect_uri regardless of
     * absoluteness, because they execute in a context the OAuth redirect
     * response does not control (e.g. a browser evaluating 'javascript:' as
     * script rather than navigating). Defense in depth: this check is not
     * the app's only protection against such schemes, but DCR must not be
     * the place a malicious client smuggles one in as a "custom app scheme".
     */
    private const DENYLISTED_SCHEMES = ['javascript', 'data', 'vbscript'];

    public static function is_valid(string $uri): bool
    {
        if ('' === $uri) {
            return false;
        }

        $parts = parse_url($uri);
        if (false === $parts) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if ('' === $scheme) {
            return false;
        }

        if (isset($parts['fragment'])) {
            return false;
        }

        if (in_array($scheme, self::DENYLISTED_SCHEMES, true)) {
            return false;
        }

        if ('http' === $scheme) {
            $host = strtolower(trim($parts['host'] ?? '', '[]'));
            return in_array($host, self::LOOPBACK_HOSTS, true);
        }

        if ('https' === $scheme) {
            return isset($parts['host']) && '' !== $parts['host'];
        }

        // Any other scheme (native app custom scheme) just needs to be an
        // absolute URI, which parse_url() having returned a non-empty scheme
        // already establishes.
        return true;
    }
}
