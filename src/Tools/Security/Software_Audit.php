<?php

namespace WPMCP\Tools\Security;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Outdated / abandoned software audit.
 *
 * Every evaluate_*() is pure. run() gathers live update transients plus bounded
 * plugins_api lookups. Read-only, self-contained: no external CVE database.
 */
class Software_Audit
{
    private const MAX_ABANDONED_LOOKUPS = 30;
    private const ABANDONED_CACHE_TTL   = 43200; // 12 hours: cache per-slug closed/open status.

    public function evaluate_core_update(bool $available, string $current, string $new): array
    {
        return $available
            ? Security_Finding::make(
                'software_core_update',
                'software',
                'WordPress core',
                'warning',
                ['current' => $current, 'new' => $new],
                sprintf('WordPress core is outdated (%s to %s).', $current, $new),
                'Update WordPress core; releases include security fixes.'
            )
            : Security_Finding::make('software_core_update', 'software', 'WordPress core', 'pass', $current, sprintf('WordPress core is up to date (%s).', $current));
    }

    /**
     * @param array<int,array{name:string,current:string,new:string}> $updates
     * @param string                                                   $kind 'plugin'|'theme'
     * @return array Finding[]
     */
    public function evaluate_updates(array $updates, string $kind): array
    {
        $label = 'theme' === $kind ? 'Outdated theme' : 'Outdated plugin';
        $id    = 'theme' === $kind ? 'software_theme_update' : 'software_plugin_update';
        $out   = [];
        foreach ($updates as $update) {
            $name    = (string) ($update['name'] ?? 'unknown');
            $current = (string) ($update['current'] ?? '?');
            $new     = (string) ($update['new'] ?? '?');
            $out[]   = Security_Finding::make(
                $id,
                'software',
                $label,
                'warning',
                ['name' => $name, 'current' => $current, 'new' => $new],
                sprintf('%s "%s" is outdated (%s to %s).', ucfirst($kind), $name, $current, $new),
                sprintf('Update %s "%s"; outdated %ss are a leading source of site compromise.', $kind, $name, $kind)
            );
        }
        return $out;
    }

    /**
     * @param string[] $slugs Plugin slugs that are closed/removed/abandoned.
     * @return array Finding[]
     */
    public function evaluate_abandoned(array $slugs): array
    {
        $out = [];
        foreach ($slugs as $slug) {
            $out[] = Security_Finding::make(
                'software_abandoned',
                'software',
                'Abandoned plugin',
                'warning',
                (string) $slug,
                sprintf('Plugin "%s" appears closed or removed from the wordpress.org directory.', $slug),
                'Plugins removed from the directory often have unpatched security issues. Replace it with a maintained alternative.'
            );
        }
        return $out;
    }

    public function evaluate_inactive(int $count): array
    {
        return $count > 0
            ? Security_Finding::make(
                'software_inactive',
                'software',
                'Inactive components',
                'info',
                $count,
                sprintf('%d inactive plugin(s)/theme(s) installed.', $count),
                'Delete plugins and themes you do not use; inactive code can still be exploited if reachable.'
            )
            : Security_Finding::make('software_inactive', 'software', 'Inactive components', 'pass', 0, 'No inactive plugins or themes.');
    }

    /**
     * Live gather.
     *
     * @return array Finding[]
     */
    public function run(): array
    {
        if (! function_exists('get_plugin_updates')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        $findings = [];

        global $wp_version;
        $core_updates = function_exists('get_core_updates') ? get_core_updates() : [];
        if (
            ! empty($core_updates) && is_array($core_updates)
            && isset($core_updates[0]->response) && 'upgrade' === $core_updates[0]->response
        ) {
            $findings[] = $this->evaluate_core_update(true, (string) $wp_version, (string) ($core_updates[0]->version ?? '?'));
        } else {
            $findings[] = $this->evaluate_core_update(false, (string) $wp_version, (string) $wp_version);
        }

        $plugin_updates = function_exists('get_plugin_updates') ? get_plugin_updates() : [];
        $plugin_norm    = [];
        foreach ((array) $plugin_updates as $data) {
            $plugin_norm[] = [
                'name'    => (string) ($data->Name ?? ''),
                'current' => (string) ($data->Version ?? '?'),
                'new'     => (string) ($data->update->new_version ?? '?'),
            ];
        }
        $findings = array_merge($findings, $this->evaluate_updates($plugin_norm, 'plugin'));

        $theme_updates = function_exists('get_theme_updates') ? get_theme_updates() : [];
        $theme_norm    = [];
        foreach ((array) $theme_updates as $theme) {
            $has_getter    = is_object($theme) && method_exists($theme, 'get');
            $theme_norm[]  = [
                'name'    => (string) ($has_getter ? $theme->get('Name') : ''),
                'current' => (string) ($has_getter ? $theme->get('Version') : '?'),
                'new'     => (string) ($theme->update['new_version'] ?? '?'),
            ];
        }
        $findings = array_merge($findings, $this->evaluate_updates($theme_norm, 'theme'));

        $all_plugins    = function_exists('get_plugins') ? get_plugins() : [];
        $active_plugins = (array) get_option('active_plugins', []);
        $inactive       = max(0, count($all_plugins) - count($active_plugins));
        $findings[]     = $this->evaluate_inactive($inactive);

        $findings = array_merge($findings, $this->evaluate_abandoned($this->detect_abandoned($active_plugins)));

        return $findings;
    }

    /**
     * Detect plugins closed/removed from the wordpress.org directory.
     *
     * Per-slug results are cached in a transient so repeat scans are instant; the
     * MAX_ABANDONED_LOOKUPS cap bounds only live API calls. A WP_Error (premium
     * plugin or API down) is neither flagged nor cached, so a transient outage is
     * retried on the next scan.
     *
     * @param string[] $active_plugins
     * @return string[] closed/removed slugs
     */
    private function detect_abandoned(array $active_plugins): array
    {
        if (! function_exists('plugins_api')) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        }
        $closed  = [];
        $checked = 0;
        foreach ($active_plugins as $file) {
            $slug = strtok((string) $file, '/');
            if ('' === $slug) {
                continue;
            }

            $cache_key = 'wpmcp_sec_abandoned_' . md5($slug);
            $cached    = get_transient($cache_key);
            if (false !== $cached) {
                if ('closed' === $cached) {
                    $closed[] = $slug;
                }
                continue;
            }

            if ($checked >= self::MAX_ABANDONED_LOOKUPS) {
                continue; // Live API-call budget spent; skip uncached slugs this run.
            }
            $checked++;

            $info = plugins_api('plugin_information', ['slug' => $slug, 'fields' => ['sections' => false]]);
            if (is_wp_error($info)) {
                continue; // Not on wordpress.org, or the API is down: do not flag, do not cache.
            }
            $state = ! empty($info->closed) ? 'closed' : 'open';
            set_transient($cache_key, $state, self::ABANDONED_CACHE_TTL);
            if ('closed' === $state) {
                $closed[] = $slug;
            }
        }
        return $closed;
    }
}
