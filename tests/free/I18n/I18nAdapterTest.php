<?php

namespace WPMCP\Tests\Free\I18n;

use WPMCP\Tools\I18n\I18n_Adapter;

/**
 * Proves the adapter's plugin detection: which multilingual plugin (if any)
 * is active, reported the same way regardless of whether Polylang or WPML is
 * running. Gated on wpmcp_i18n_plugin() so it skips cleanly when neither is
 * installed.
 */
class I18nAdapterTest extends \WP_UnitTestCase
{
    public function test_detects_the_active_plugin(): void
    {
        $active = wpmcp_i18n_plugin();
        if ('' === $active) {
            $this->markTestSkipped('No i18n plugin active');
        }

        $this->assertSame($active, I18n_Adapter::active_plugin());
    }

    public function test_reports_no_plugin_when_neither_is_active(): void
    {
        if ('' !== wpmcp_i18n_plugin()) {
            $this->markTestSkipped('An i18n plugin is active in this test environment.');
        }

        $this->assertSame('', I18n_Adapter::active_plugin());
    }

    public function test_normalize_polylang_languages_maps_to_neutral_shape(): void
    {
        $en = new \stdClass();
        $en->slug       = 'en';
        $en->name       = 'English';
        $en->is_default = true;

        $fr = new \stdClass();
        $fr->slug       = 'fr';
        $fr->name       = 'Francais';
        $fr->is_default = false;

        $out = I18n_Adapter::normalize_polylang_languages([$en, $fr]);

        $this->assertSame(
            [
                ['code' => 'en', 'name' => 'English', 'is_default' => true],
                ['code' => 'fr', 'name' => 'Francais', 'is_default' => false],
            ],
            $out
        );
    }

    public function test_normalize_wpml_languages_maps_to_neutral_shape(): void
    {
        $raw = [
            'en' => ['code' => 'en', 'native_name' => 'English'],
            'fr' => ['code' => 'fr', 'native_name' => 'Francais'],
        ];

        $out = I18n_Adapter::normalize_wpml_languages($raw, 'en');

        $this->assertSame(
            [
                ['code' => 'en', 'name' => 'English', 'is_default' => true],
                ['code' => 'fr', 'name' => 'Francais', 'is_default' => false],
            ],
            $out
        );
    }

    public function test_normalize_translations_enriches_with_titles(): void
    {
        $en = $this->factory()->post->create(['post_title' => 'Hello']);
        $fr = $this->factory()->post->create(['post_title' => 'Bonjour']);

        $out = I18n_Adapter::normalize_translations(['en' => $en, 'fr' => $fr]);

        $this->assertSame(
            [
                'en' => ['post_id' => $en, 'title' => 'Hello'],
                'fr' => ['post_id' => $fr, 'title' => 'Bonjour'],
            ],
            $out
        );

        wp_delete_post($en, true);
        wp_delete_post($fr, true);
    }
}
