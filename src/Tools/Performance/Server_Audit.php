<?php

namespace WPMCP\Tools\Performance;

if (! defined('ABSPATH')) {
    exit;
}

class Server_Audit
{
    private const MIN_MEMORY_BYTES = 134217728; // 128 MB

    public function evaluate_php_version(string $version): array
    {
        if (version_compare($version, '8.2', '>=')) {
            return Finding::make(
                'php_version',
                'server',
                'PHP version',
                'pass',
                $version,
                sprintf('PHP %s is current and supported.', $version)
            );
        }
        if (version_compare($version, '8.0', '>=')) {
            return Finding::make(
                'php_version',
                'server',
                'PHP version',
                'warning',
                $version,
                sprintf('PHP %s is nearing end of life.', $version),
                'Upgrade to PHP 8.2 or newer for better performance and security support.'
            );
        }
        return Finding::make(
            'php_version',
            'server',
            'PHP version',
            'critical',
            $version,
            sprintf('PHP %s is end-of-life and unsupported.', $version),
            'Upgrade to PHP 8.2+ immediately; old PHP is slow and a security risk.'
        );
    }

    public function evaluate_memory_limit(string $limit): array
    {
        $bytes = $this->to_bytes($limit);
        if ($bytes < 0) { // -1 = unlimited.
            return Finding::make('memory_limit', 'server', 'PHP memory limit', 'pass', $limit, 'PHP memory is unlimited.');
        }
        if ($bytes >= self::MIN_MEMORY_BYTES) {
            return Finding::make(
                'memory_limit',
                'server',
                'PHP memory limit',
                'pass',
                $limit,
                sprintf('PHP memory limit is %s.', $limit)
            );
        }
        return Finding::make(
            'memory_limit',
            'server',
            'PHP memory limit',
            'warning',
            $limit,
            sprintf('PHP memory limit is only %s.', $limit),
            'Raise memory_limit (and WP_MEMORY_LIMIT) to at least 128M to avoid out-of-memory errors under load.'
        );
    }

    public function evaluate_opcache(bool $enabled): array
    {
        return $enabled
            ? Finding::make('opcache', 'server', 'PHP OPcache', 'pass', true, 'OPcache is enabled.')
            : Finding::make(
                'opcache',
                'server',
                'PHP OPcache',
                'warning',
                false,
                'OPcache is disabled.',
                'Enable the Zend OPcache extension, it caches compiled PHP and dramatically reduces request time.'
            );
    }

    public function evaluate_object_cache(bool $persistent): array
    {
        return $persistent
            ? Finding::make('object_cache', 'server', 'Persistent object cache', 'pass', true, 'A persistent object cache is active.')
            : Finding::make(
                'object_cache',
                'server',
                'Persistent object cache',
                'warning',
                false,
                'No persistent object cache detected.',
                'Add Redis or Memcached with a drop-in (for example redis-cache) to cache DB queries across requests.'
            );
    }

    public function evaluate_image_lib(bool $imagick, bool $gd): array
    {
        if ($imagick || $gd) {
            return Finding::make(
                'image_lib',
                'server',
                'Image library',
                'pass',
                $imagick ? 'imagick' : 'gd',
                'An image processing library is available.'
            );
        }
        return Finding::make(
            'image_lib',
            'server',
            'Image library',
            'warning',
            'none',
            'No image library (Imagick/GD) detected.',
            'Install Imagick or GD so WordPress can generate optimized image sizes.'
        );
    }

    public function evaluate_wp_debug(bool $on, string $environment): array
    {
        if (! $on) {
            return Finding::make('wp_debug', 'config', 'WP_DEBUG', 'pass', false, 'WP_DEBUG is off.');
        }
        if ('production' === $environment) {
            return Finding::make(
                'wp_debug',
                'config',
                'WP_DEBUG',
                'warning',
                true,
                'WP_DEBUG is ON in production.',
                'Turn off WP_DEBUG on production, debug logging and notices add overhead and leak information.'
            );
        }
        return Finding::make(
            'wp_debug',
            'config',
            'WP_DEBUG',
            'info',
            true,
            sprintf('WP_DEBUG is on (environment: %s).', $environment)
        );
    }

    private function to_bytes(string $value): int
    {
        $value = trim($value);
        if ('-1' === $value) {
            return -1;
        }
        $unit = strtolower(substr($value, -1));
        $num  = (int) $value;
        switch ($unit) {
            case 'g':
                return $num * 1024 * 1024 * 1024;
            case 'm':
                return $num * 1024 * 1024;
            case 'k':
                return $num * 1024;
            default:
                return (int) $value;
        }
    }
}
