<?php

namespace WPMCP\Tests\Free\Connect;

use WPMCP\MCP\Ability;
use WPMCP\MCP\Registrar;
use WPMCP\Plugin;
use WPMCP\Pro\Gate;
use WPMCP\Tools\Analysis\Analyze_Seo;
use WPMCP\Tools\Connect\List_Tool_Catalog;

class ListToolCatalogTest extends \WP_UnitTestCase
{
    public static function wpSetUpBeforeClass(): void
    {
        // Same rationale as PluginAbilitiesTest: the shared Registrar is
        // only populated once, the first time wp_abilities_api_init fires.
        if (0 === did_action('wp_abilities_api_init')) {
            do_action('wp_abilities_api_init');
        }
    }

    protected function tearDown(): void
    {
        Gate::set_pro_for_tests(null);
        parent::tearDown();
    }

    public function test_entries_are_grouped_by_domain(): void
    {
        $out = (new List_Tool_Catalog())->handle([]);

        $this->assertArrayHasKey('domains', $out);
        $this->assertArrayHasKey('core', $out['domains']);
        $this->assertArrayHasKey('connect', $out['domains']);

        foreach ($out['domains']['core'] as $entry) {
            $this->assertArrayHasKey('name', $entry);
            $this->assertArrayHasKey('tier', $entry);
            $this->assertArrayHasKey('operation', $entry);
            $this->assertArrayHasKey('capability', $entry);
            $this->assertArrayHasKey('read_only_hint', $entry);
            $this->assertArrayHasKey('destructive_hint', $entry);
        }
    }

    public function test_includes_a_known_free_core_ability(): void
    {
        $out = (new List_Tool_Catalog())->handle([]);

        $names = array_column($out['domains']['core'], 'name');
        $this->assertContains('wpmcp/get-page', $names);

        $by_name = array_combine($names, $out['domains']['core']);
        $this->assertSame('free', $by_name['wpmcp/get-page']['tier']);
        $this->assertSame('read', $by_name['wpmcp/get-page']['operation']);
        $this->assertTrue($by_name['wpmcp/get-page']['read_only_hint']);
    }

    public function test_domain_filter_narrows_entries_and_summary(): void
    {
        $out = (new List_Tool_Catalog())->handle(['domain' => 'connect']);

        $this->assertSame(['connect'], array_keys($out['domains']));
        $this->assertSame(['connect'], array_keys($out['summary']));

        $names = array_column($out['domains']['connect'], 'name');
        $this->assertContains('wpmcp/get-connection-info', $names);
        $this->assertContains('wpmcp/list-tool-catalog', $names);
    }

    public function test_summary_reports_a_count_per_domain(): void
    {
        $out = (new List_Tool_Catalog())->handle([]);

        foreach ($out['domains'] as $domain => $entries) {
            $this->assertSame(count($entries), $out['summary'][$domain]);
        }
    }

    public function test_reports_a_known_pro_ability_under_analysis_when_registered(): void
    {
        // Plugin::boot() registers abilities once at wp_abilities_api_init
        // against whatever Gate::is_pro() returned at that moment, so it
        // cannot be re-exercised per-test against a toggled Gate (see
        // ElementorDeepAbilitiesRegistrationTest for the same pattern). This
        // builds the same Ability wpmcp/analyze-seo's registration in
        // Plugin::register_analysis_abilities() constructs and drives it
        // through a fresh, pro-gated Registrar instead.
        Gate::set_pro_for_tests(true);

        $registrar = new Registrar();
        $registrar->register(new Ability(
            'wpmcp/analyze-seo',
            'pro',
            'Score a post\'s on-page SEO.',
            [
                'type'       => 'object',
                'properties' => [
                    'post_id'       => ['type' => 'integer'],
                    'focus_keyword' => ['type' => 'string'],
                ],
                'required'   => ['post_id'],
            ],
            [new Analyze_Seo(), 'handle'],
            'edit_posts',
            'analysis',
            'read'
        ));

        $out = (new List_Tool_Catalog())->handle([], $registrar);

        $names = array_column($out['domains']['analysis'], 'name');
        $this->assertContains('wpmcp/analyze-seo', $names);

        $by_name = array_combine($names, $out['domains']['analysis']);
        $this->assertSame('pro', $by_name['wpmcp/analyze-seo']['tier']);
        $this->assertSame('read', $by_name['wpmcp/analyze-seo']['operation']);
    }

    public function test_tier_filter_narrows_to_matching_abilities(): void
    {
        Gate::set_pro_for_tests(true);

        $registrar = new Registrar();
        $registrar->register(new Ability(
            'wpmcp/analyze-seo',
            'pro',
            'Score a post\'s on-page SEO.',
            [
                'type'       => 'object',
                'properties' => ['post_id' => ['type' => 'integer']],
                'required'   => ['post_id'],
            ],
            [new Analyze_Seo(), 'handle'],
            'edit_posts',
            'analysis',
            'read'
        ));

        $out = (new List_Tool_Catalog())->handle(['tier' => 'pro'], $registrar);

        $this->assertSame(['analysis'], array_keys($out['domains']));
        $names = array_column($out['domains']['analysis'], 'name');
        $this->assertSame(['wpmcp/analyze-seo'], $names);
    }
}
