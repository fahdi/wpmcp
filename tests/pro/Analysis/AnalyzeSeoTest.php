<?php

namespace WPMCP\Tests\Pro\Analysis;

use WPMCP\MCP\{Ability, Registrar};
use WPMCP\Pro\Gate;
use WPMCP\Tools\Analysis\Analyze_Seo;

class AnalyzeSeoTest extends \WP_UnitTestCase
{
    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();
        Gate::set_pro_for_tests(true);
    }

    protected function tearDown(): void
    {
        foreach ($this->created as $id) {
            wp_delete_post($id, true);
        }
        $this->created = [];
        Gate::set_pro_for_tests(null);
        parent::tearDown();
    }

    private function post(array $args): int
    {
        $id = $this->factory()->post->create($args);
        $this->created[] = $id;
        return $id;
    }

    private function status_map(array $out): array
    {
        return array_column($out['report']['checks'], 'status', 'id');
    }

    public function test_flags_short_title_and_missing_meta_description(): void
    {
        // A too-short title and no excerpt/description available.
        $id = $this->post([
            'post_title'   => 'Hi',
            'post_excerpt' => '',
            'post_content' => '<h1>Hi</h1><p>Short body copy.</p>',
        ]);

        $out    = (new Analyze_Seo())->handle(['post_id' => $id]);
        $status = $this->status_map($out);

        $this->assertSame($id, $out['post_id']);
        $this->assertSame('fail', $status['title_length']);
        $this->assertSame('fail', $status['meta_description']);
        $this->assertIsInt($out['report']['score']);
        $this->assertLessThan(100, $out['report']['score']);
    }

    public function test_uses_excerpt_as_meta_description_fallback(): void
    {
        $id = $this->post([
            'post_title'   => 'A Reasonably Descriptive Page Title Here',
            'post_excerpt' => 'This excerpt is long enough to serve as a usable meta description for the search snippet on this page today.',
            'post_content' => '<h1>Heading</h1><p>Body.</p>',
        ]);

        $out    = (new Analyze_Seo())->handle(['post_id' => $id]);
        $status = $this->status_map($out);

        $this->assertNotSame('fail', $status['meta_description']);
    }

    public function test_missing_post_id_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Analyze_Seo())->handle([]);
    }

    private function make_ability(): Ability
    {
        return new Ability(
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
        );
    }

    public function test_registrar_skips_when_free(): void
    {
        Gate::set_pro_for_tests(false);
        $registrar = new Registrar();
        $registrar->register($this->make_ability());
        $this->assertCount(0, $registrar->all());
    }

    public function test_registrar_keeps_when_pro(): void
    {
        Gate::set_pro_for_tests(true);
        $registrar = new Registrar();
        $registrar->register($this->make_ability());
        $names = array_map(fn($a) => $a->name, $registrar->all());
        $this->assertContains('wpmcp/analyze-seo', $names);
    }
}
