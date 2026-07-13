<?php

namespace WPMCP\Tools\Multisite;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only network introspection, backed by WordPress's own multisite API
 * (is_multisite(), get_network(), get_main_site_id(), get_sites(),
 * get_site(), get_blog_details()). Kept thin and adapter-shaped so the tool
 * classes in this namespace stay simple wrappers around argument validation
 * plus a call into here, mirroring how I18n_Adapter and SEO_Adapter sit in
 * front of their respective plugin APIs.
 *
 * This is deliberately READ-ONLY. Fleet-management writes (creating,
 * deleting, archiving, or activating sites) are out of scope for this tool
 * group: those operations are not covered by the plugin's snapshot/rollback
 * safety model (Safe_Mutation only understands single-site content, options,
 * and postmeta, not whole-site lifecycle), and exposing them here would
 * violate the product's "nothing unrecoverable" promise. See issue #35 and
 * the registration comment in Plugin.php for the full rationale.
 *
 * Honesty note on test coverage: this unit-test harness boots WordPress in
 * single-site mode, so is_multisite() is always false here and the live
 * network paths below (network_info(), list_sites(), site_details()) can
 * never actually run against a real network in CI. That is a production-only
 * gap, not an oversight; the pure normalization helpers (normalize_site(),
 * normalize_sites(), clamp_limit(), clamp_offset()) are unit-tested directly
 * against hand-built fixtures so the load-bearing mapping logic still has
 * coverage. This mirrors how I18n_Adapter's Polylang/WPML live-fetch paths
 * are treated: adapter detection and pure normalization are tested, the
 * real plugin round-trip is not.
 */
class Multisite_Adapter
{
    public const DEFAULT_LIMIT = 50;
    public const MAX_LIMIT     = 500;

    /**
     * Whether this WordPress install is a multisite network at all.
     */
    public static function is_network(): bool
    {
        return is_multisite();
    }

    /**
     * Network id/name/domain, total site count, and the main site's blog id.
     * Returns a WP_Error when called outside a network: get_network() has
     * nothing meaningful to report on a single-site install.
     *
     * @return array|\WP_Error
     */
    public static function network_info()
    {
        if (! is_multisite()) {
            return new \WP_Error(
                'wpmcp_not_multisite',
                'This site is not part of a WordPress multisite network.'
            );
        }

        $network = get_network();
        if (! $network) {
            return new \WP_Error(
                'wpmcp_network_unavailable',
                'Network information is not available on this install.'
            );
        }

        return [
            'network_id'   => (int) $network->id,
            'name'         => (string) ($network->site_name ?? ''),
            'domain'       => (string) $network->domain,
            'site_count'   => (int) get_blog_count(),
            'main_site_id' => (int) get_main_site_id(),
        ];
    }

    /**
     * Paginated list of sites on the network, normalized to the neutral
     * shape. limit/offset are already expected to be validated/clamped by
     * the caller (List_Network_Sites) before this is invoked. Returns a
     * WP_Error when called outside a network.
     *
     * @return array|\WP_Error
     */
    public static function list_sites(int $limit, int $offset)
    {
        if (! is_multisite()) {
            return new \WP_Error(
                'wpmcp_not_multisite',
                'This site is not part of a WordPress multisite network.'
            );
        }

        $sites = get_sites([
            'number' => $limit,
            'offset' => $offset,
        ]);

        return self::normalize_sites(is_array($sites) ? $sites : []);
    }

    /**
     * A single site's details by blog id. Returns a WP_Error both when
     * called outside a network and when the blog id does not resolve to a
     * real site, so callers get a clear message either way rather than a
     * null/empty result.
     *
     * @return array|\WP_Error
     */
    public static function site_details(int $blog_id)
    {
        if (! is_multisite()) {
            return new \WP_Error(
                'wpmcp_not_multisite',
                'This site is not part of a WordPress multisite network.'
            );
        }

        $site = get_site($blog_id);
        if (! $site) {
            return new \WP_Error(
                'wpmcp_site_not_found',
                sprintf('No site found for blog_id %d.', $blog_id)
            );
        }

        $details = function_exists('get_blog_details') ? get_blog_details($blog_id) : null;
        $name    = $details && isset($details->blogname) ? (string) $details->blogname : '';

        return self::normalize_site($site, $name);
    }

    /**
     * Normalize a WP_Site (or WP_Site-shaped) object to the neutral shape:
     * ['blog_id' => int, 'url' => string, 'name' => string,
     * 'last_updated' => string]. Pure aside from reading object properties,
     * so it is testable with plain stdClass fixtures rather than a real
     * network-installed WP_Site.
     *
     * $name is passed in separately (rather than read off the site object)
     * because WP_Site itself does not carry a blog name; only
     * get_blog_details() does. Callers that don't have a name yet (e.g. a
     * bulk list_sites() call that wants to stay cheap) can omit it.
     */
    public static function normalize_site(object $site, string $name = ''): array
    {
        $domain = (string) ($site->domain ?? '');
        $path   = (string) ($site->path ?? '');

        return [
            'blog_id'      => (int) ($site->blog_id ?? 0),
            'url'          => $domain . $path,
            'name'         => $name,
            'last_updated' => (string) ($site->last_updated ?? ''),
        ];
    }

    /**
     * Normalize a list of WP_Site (or WP_Site-shaped) objects via
     * normalize_site(). Pure, testable with an array of stdClass fixtures.
     *
     * @param object[] $sites
     */
    public static function normalize_sites(array $sites): array
    {
        return array_map(static function ($site) {
            return self::normalize_site($site);
        }, $sites);
    }

    /**
     * Clamp a caller-supplied limit to [1, MAX_LIMIT], defaulting to
     * DEFAULT_LIMIT when null. Mirrors List_Transients's limit clamping so
     * a network with many sites cannot be used to dump an unbounded result
     * set in one call.
     */
    public static function clamp_limit(?int $limit): int
    {
        $limit = $limit ?? self::DEFAULT_LIMIT;
        $limit = max(1, $limit);

        return min($limit, self::MAX_LIMIT);
    }

    /**
     * Clamp a caller-supplied offset to a non-negative integer, defaulting
     * to 0 when null.
     */
    public static function clamp_offset(?int $offset): int
    {
        $offset = $offset ?? 0;

        return max(0, $offset);
    }
}
