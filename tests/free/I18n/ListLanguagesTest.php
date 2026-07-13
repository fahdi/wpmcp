<?php

namespace WPMCP\Tests\Free\I18n;

use WPMCP\Tools\I18n\List_Languages;
use WPMCP\Tools\I18n\I18n_Adapter;

/**
 * Read tool: returns the site's configured languages under a 'languages' key.
 *
 * The real Polylang fetch path (pll_languages_list) cannot run in the unit
 * harness because Polylang's API is not booted there, so the returned list is
 * empty against the un-booted plugin. This test asserts the tool's own
 * contract (the 'languages' key is always present and mirrors the adapter),
 * which holds regardless of whether any language is configured.
 */
class ListLanguagesTest extends \WP_UnitTestCase
{
    public function test_returns_languages_key(): void
    {
        $out = (new List_Languages())->handle([]);

        $this->assertArrayHasKey('languages', $out);
        $this->assertIsArray($out['languages']);
        $this->assertSame(I18n_Adapter::list_languages(), $out['languages']);
    }
}
