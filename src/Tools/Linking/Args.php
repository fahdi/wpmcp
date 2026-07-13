<?php

namespace WPMCP\Tools\Linking;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Shared argument normalization for the Linking tools: coerce the post-type
 * filter to a validated list, and clamp the scan and result caps to sane
 * bounds so a caller cannot request an unbounded amount of work.
 */
class Args
{
    /**
     * @param mixed $value A post type string or an array of them.
     * @return string[] Existing post types, defaulting to ['post'] when none resolve.
     */
    public static function post_types($value): array
    {
        $candidates = is_array($value) ? $value : [$value];
        $types      = [];
        foreach ($candidates as $type) {
            $type = is_string($type) ? trim($type) : '';
            if ('' !== $type && post_type_exists($type) && ! in_array($type, $types, true)) {
                $types[] = $type;
            }
        }

        return [] === $types ? ['post'] : $types;
    }

    public static function scan_limit($value, int $default): int
    {
        $value = is_numeric($value) ? (int) $value : $default;
        if ($value < 1) {
            $value = $default;
        }

        return min($value, Link_Graph::MAX_SCAN);
    }

    public static function cap($value, int $default): int
    {
        $value = is_numeric($value) ? (int) $value : $default;

        return $value < 1 ? $default : $value;
    }
}
