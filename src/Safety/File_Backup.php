<?php

namespace WPMCP\Safety;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Backs up an attachment's physical files (the main file plus every
 * intermediate size, and the pre-scale original when one exists) before an
 * irreversible delete, so Rollback_Service can put the bytes back on disk
 * after the DB record is resurrected. See issue #24.
 */
class File_Backup
{
    /**
     * Every absolute file path belonging to an attachment: the main file,
     * each registered intermediate size from wp_get_attachment_metadata(),
     * and the pre-"-scaled" original when WordPress downsized the upload on
     * import. Paths are deduplicated and any size entry whose file is
     * already missing from disk is skipped (nothing to back up).
     */
    public static function collect_attachment_files(int $attachment_id): array
    {
        $main = get_attached_file($attachment_id);
        if (! $main) {
            return [];
        }

        $dir   = trailingslashit(dirname($main));
        $paths = [$main];

        $meta = wp_get_attachment_metadata($attachment_id);
        if (is_array($meta) && ! empty($meta['sizes']) && is_array($meta['sizes'])) {
            foreach ($meta['sizes'] as $size) {
                if (! empty($size['file'])) {
                    $paths[] = $dir . $size['file'];
                }
            }
        }

        // WordPress may have downsized a large original on upload, keeping
        // the pre-scale original alongside it (e.g. "photo-scaled.jpg" is
        // the attached file, "photo.jpg" is the untouched original).
        if (is_array($meta) && ! empty($meta['original_image'])) {
            $paths[] = $dir . $meta['original_image'];
        }

        $paths = array_values(array_unique($paths));

        return array_values(array_filter($paths, 'is_file'));
    }
}
