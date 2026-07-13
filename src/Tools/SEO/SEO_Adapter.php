<?php

namespace WPMCP\Tools\SEO;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Maps a neutral SEO field set to the active plugin's postmeta keys, so the
 * SEO tools work identically against either Yoast SEO or RankMath without the
 * tool classes themselves knowing which plugin is installed.
 *
 * Only one plugin is expected to be active on a given site. When detection
 * finds both (not expected, but not impossible), Yoast wins, matching
 * wpmcp_seo_plugin()'s test-harness precedence.
 *
 * The neutral fields are: title, description, focus_keyword, canonical,
 * noindex, nofollow. noindex/nofollow are booleans here even though Yoast
 * stores them as the strings '0'/'1' on the post: the adapter normalizes
 * that string-vs-bool difference on both read and write so callers only ever
 * deal with booleans.
 */
class SEO_Adapter
{
    private const YOAST_KEYS = [
        'title'         => '_yoast_wpseo_title',
        'description'   => '_yoast_wpseo_metadesc',
        'focus_keyword' => '_yoast_wpseo_focuskw',
        'canonical'     => '_yoast_wpseo_canonical',
        'noindex'       => '_yoast_wpseo_meta-robots-noindex',
        'nofollow'      => '_yoast_wpseo_meta-robots-nofollow',
    ];

    private const RANKMATH_KEYS = [
        'title'         => 'rank_math_title',
        'description'   => 'rank_math_description',
        'focus_keyword' => 'rank_math_focus_keyword',
        'canonical'     => 'rank_math_canonical_url',
        'noindex'       => 'rank_math_robots',
        'nofollow'      => 'rank_math_robots',
    ];

    /**
     * Which SEO plugin is active: 'yoast', 'rankmath', or '' when neither is.
     */
    public static function active_plugin(): string
    {
        if (defined('WPSEO_VERSION') || class_exists('WPSEO_Options')) {
            return 'yoast';
        }

        if (class_exists('RankMath')) {
            return 'rankmath';
        }

        return '';
    }

    /**
     * Human-readable plugin name and version for get-seo-status, or null when
     * no supported SEO plugin is active.
     */
    public static function plugin_info(): ?array
    {
        $active = self::active_plugin();

        if ('yoast' === $active) {
            return [
                'plugin'  => 'yoast',
                'name'    => 'Yoast SEO',
                'version' => defined('WPSEO_VERSION') ? WPSEO_VERSION : '',
            ];
        }

        if ('rankmath' === $active) {
            return [
                'plugin'  => 'rankmath',
                'name'    => 'Rank Math',
                'version' => defined('RANK_MATH_VERSION') ? RANK_MATH_VERSION : '',
            ];
        }

        return null;
    }

    /**
     * The postmeta key map for the active plugin, or an empty array when no
     * supported SEO plugin is active.
     */
    public static function meta_keys(): array
    {
        $active = self::active_plugin();

        if ('yoast' === $active) {
            return self::YOAST_KEYS;
        }

        if ('rankmath' === $active) {
            return self::RANKMATH_KEYS;
        }

        return [];
    }

    /**
     * Read the neutral SEO field set for a post from the active plugin's
     * postmeta keys. noindex/nofollow are normalized to booleans regardless
     * of how the active plugin stores them on the post.
     */
    public static function get_meta(int $post_id): array
    {
        $keys   = self::meta_keys();
        $active = self::active_plugin();

        if ([] === $keys) {
            return [
                'title'         => '',
                'description'   => '',
                'focus_keyword' => '',
                'canonical'     => '',
                'noindex'       => false,
                'nofollow'      => false,
            ];
        }

        $out = [
            'title'         => (string) get_post_meta($post_id, $keys['title'], true),
            'description'   => (string) get_post_meta($post_id, $keys['description'], true),
            'focus_keyword' => (string) get_post_meta($post_id, $keys['focus_keyword'], true),
            'canonical'     => (string) get_post_meta($post_id, $keys['canonical'], true),
        ];

        if ('yoast' === $active) {
            $out['noindex']  = '1' === (string) get_post_meta($post_id, $keys['noindex'], true);
            $out['nofollow'] = '1' === (string) get_post_meta($post_id, $keys['nofollow'], true);
        } elseif ('rankmath' === $active) {
            $robots           = get_post_meta($post_id, $keys['noindex'], true);
            $robots           = is_array($robots) ? $robots : [];
            $out['noindex']   = in_array('noindex', $robots, true);
            $out['nofollow']  = in_array('nofollow', $robots, true);
        } else {
            $out['noindex']  = false;
            $out['nofollow'] = false;
        }

        return $out;
    }

    /**
     * Write a subset of the neutral SEO field set to a post via
     * update_post_meta(), translated to the active plugin's keys and storage
     * format. Only keys present in $fields are written; omitted fields are
     * left untouched. Callers are expected to route this through
     * Safe_Mutation themselves so the change is snapshotted and undoable;
     * this method performs the raw postmeta writes only.
     */
    public static function update_meta(int $post_id, array $fields): void
    {
        $keys   = self::meta_keys();
        $active = self::active_plugin();

        if ([] === $keys) {
            return;
        }

        foreach (['title', 'description', 'focus_keyword', 'canonical'] as $field) {
            if (array_key_exists($field, $fields)) {
                update_post_meta($post_id, $keys[$field], (string) $fields[$field]);
            }
        }

        if ('yoast' === $active) {
            if (array_key_exists('noindex', $fields)) {
                update_post_meta($post_id, $keys['noindex'], $fields['noindex'] ? '1' : '0');
            }
            if (array_key_exists('nofollow', $fields)) {
                update_post_meta($post_id, $keys['nofollow'], $fields['nofollow'] ? '1' : '0');
            }
            return;
        }

        if ('rankmath' === $active && (array_key_exists('noindex', $fields) || array_key_exists('nofollow', $fields))) {
            $robots  = get_post_meta($post_id, $keys['noindex'], true);
            $robots  = is_array($robots) ? $robots : [];
            $noindex = array_key_exists('noindex', $fields) ? (bool) $fields['noindex'] : in_array('noindex', $robots, true);
            $nofollow = array_key_exists('nofollow', $fields) ? (bool) $fields['nofollow'] : in_array('nofollow', $robots, true);

            $new_robots = [];
            if ($noindex) {
                $new_robots[] = 'noindex';
            }
            if ($nofollow) {
                $new_robots[] = 'nofollow';
            }

            update_post_meta($post_id, $keys['noindex'], $new_robots);
        }
    }
}
