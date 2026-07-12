<?php

namespace WPMCP\Tools\Cache;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Report which caching layers are active on this site.
 *
 * Read-only: inspects the persistent object cache backend, OPcache, and any
 * active page-cache plugin (detected by signature functions/constants). It does
 * not mutate any state, so it is not routed through the safety core.
 */
class Get_Cache_Status
{
    private Page_Cache_Detector $detector;

    public function __construct(?Page_Cache_Detector $detector = null)
    {
        $this->detector = $detector ?? new Page_Cache_Detector();
    }

    public function handle(array $args): array
    {
        return [
            'object_cache' => $this->object_cache_status(),
            'opcache'      => $this->opcache_status(),
            'page_cache'   => $this->detector->detect(),
        ];
    }

    /**
     * @return array{persistent: bool, backend: string}
     */
    private function object_cache_status(): array
    {
        $persistent = function_exists('wp_using_ext_object_cache') && wp_using_ext_object_cache();

        return [
            'persistent' => $persistent,
            'backend'    => $persistent ? 'external' : 'internal',
        ];
    }

    /**
     * @return array{available: bool, enabled: bool}
     */
    private function opcache_status(): array
    {
        $available = function_exists('opcache_get_status');
        $enabled   = $available && (bool) ini_get('opcache.enable');

        return [
            'available' => $available,
            'enabled'   => $enabled,
        ];
    }
}
