<?php

namespace WPMCP\Tools\Export;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Shared location for generated WXR export files: a dedicated directory
 * under uploads, blocked from direct web access the same way
 * WPMCP\Safety\File_Backup protects its backup directory.
 */
class Export_Dir
{
    public const DIR_NAME = 'wpmcp-exports';

    public static function path(): string
    {
        $uploads = wp_upload_dir();
        return trailingslashit($uploads['basedir']) . self::DIR_NAME;
    }

    /** Ensure the directory exists and is blocked from direct web access. */
    public static function protect(string $dir): void
    {
        wp_mkdir_p($dir);
        if (! is_file($dir . '/.htaccess')) {
            @file_put_contents($dir . '/.htaccess', "Require all denied\n");
        }
        if (! is_file($dir . '/index.php')) {
            @file_put_contents($dir . '/index.php', "<?php\n// Silence is golden.\n");
        }
    }
}
