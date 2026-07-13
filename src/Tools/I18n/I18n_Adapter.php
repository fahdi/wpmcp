<?php

namespace WPMCP\Tools\I18n;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Maps a neutral multilingual field set (languages, per-post translations,
 * a post's language) to the active i18n plugin's own API, so the i18n tools
 * work identically against either Polylang or WPML without the tool classes
 * themselves knowing which plugin is installed.
 *
 * Only one plugin is expected to be active on a given site. When detection
 * finds both (not expected, but not impossible), Polylang wins, matching
 * wpmcp_i18n_plugin()'s test-harness precedence.
 *
 * The neutral shapes are:
 *  - a language: ['code' => string, 'name' => string, 'is_default' => bool]
 *  - a post's translations: [lang_code => ['post_id' => int, 'title' => string]]
 *
 * The fetch-and-write methods delegate straight to the active plugin's
 * functions. The normalization from each plugin's raw shape to the neutral
 * shape lives in separate pure static helpers (normalize_polylang_languages,
 * normalize_wpml_languages, etc.) so that mapping logic is unit-testable with
 * fake data, without booting the real plugin. This matters for Polylang in
 * particular: in the unit-test harness its POLYLANG_VERSION constant is
 * defined (so detection reports 'polylang') but its pll_* API functions and
 * $GLOBALS['polylang'] context are never booted (that only happens on a real
 * plugins_loaded/pll_init cycle), so the live fetch paths cannot be exercised
 * there. The pure normalizers let the load-bearing mapping still be tested.
 *
 * WPML support is written best-effort against WPML's public API. WPML is a
 * paid plugin not available from wordpress.org, so it is NOT installed in the
 * test harness and the WPML paths are untested against a real WPML install.
 */
class I18n_Adapter
{
    /**
     * Which i18n plugin is active: 'polylang', 'wpml', or '' when neither is.
     * Polylang is checked first, so it wins if both somehow report active.
     */
    public static function active_plugin(): string
    {
        if (function_exists('pll_the_languages') || defined('POLYLANG_VERSION')) {
            return 'polylang';
        }

        if (defined('ICL_SITEPRESS_VERSION')) {
            return 'wpml';
        }

        return '';
    }

    /**
     * The site's configured languages in the neutral shape, from the active
     * plugin. Each entry is ['code' => string, 'name' => string,
     * 'is_default' => bool]. Returns an empty array when no i18n plugin is
     * active.
     */
    public static function list_languages(): array
    {
        $active = self::active_plugin();

        if ('polylang' === $active && function_exists('pll_languages_list')) {
            // pll_languages_list(['fields' => '']) returns the full
            // PLL_Language objects (slug, name, is_default), rather than the
            // default slug-only list.
            $languages = pll_languages_list(['fields' => '']);
            return self::normalize_polylang_languages(is_array($languages) ? $languages : []);
        }

        if ('wpml' === $active && function_exists('icl_get_languages')) {
            $languages = icl_get_languages('skip_missing=0');
            $default   = function_exists('icl_get_default_language') ? (string) icl_get_default_language() : '';
            return self::normalize_wpml_languages(is_array($languages) ? $languages : [], $default);
        }

        return [];
    }

    /**
     * Normalize Polylang's PLL_Language objects to the neutral language shape.
     * Pure: takes already-fetched objects so it is testable without booting
     * Polylang. Each object is expected to expose ->slug, ->name, and
     * ->is_default.
     */
    public static function normalize_polylang_languages(array $languages): array
    {
        $out = [];

        foreach ($languages as $language) {
            $out[] = [
                'code'       => (string) ($language->slug ?? ''),
                'name'       => (string) ($language->name ?? ''),
                'is_default' => (bool) ($language->is_default ?? false),
            ];
        }

        return $out;
    }

    /**
     * Normalize WPML's icl_get_languages() array (keyed by code, with a
     * native_name per entry) to the neutral language shape, marking the
     * entry whose code matches $default_code as the default. Pure, so it is
     * testable with fake data even though WPML itself is not installed here.
     */
    public static function normalize_wpml_languages(array $languages, string $default_code): array
    {
        $out = [];

        foreach ($languages as $code => $language) {
            $code = (string) ($language['code'] ?? $code);
            $out[] = [
                'code'       => $code,
                'name'       => (string) ($language['native_name'] ?? ''),
                'is_default' => $code === $default_code,
            ];
        }

        return $out;
    }
}
