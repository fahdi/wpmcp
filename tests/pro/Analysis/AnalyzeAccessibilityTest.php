<?php

namespace WPMCP\Tests\Pro\Analysis;

use WPMCP\MCP\{Ability, Registrar};
use WPMCP\Pro\Gate;
use WPMCP\Tools\Analysis\Analyze_Accessibility;

class AnalyzeAccessibilityTest extends \WP_UnitTestCase
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

    private function post(string $content): int
    {
        $id = $this->factory()->post->create(['post_content' => $content]);
        $this->created[] = $id;
        return $id;
    }

    private function status_map(array $out): array
    {
        return array_column($out['report']['checks'], 'status', 'id');
    }

    public function test_flags_missing_alt_and_heading_jump(): void
    {
        $id = $this->post(
            '<h1>Welcome</h1><h3>Skipped down to three</h3>'
            . '<img src="a.jpg" alt="Described"><img src="b.jpg">'
        );

        $out    = (new Analyze_Accessibility())->handle(['post_id' => $id]);
        $status = $this->status_map($out);

        $this->assertSame($id, $out['post_id']);
        $this->assertSame('fail', $status['image_alts']);
        $this->assertSame('warn', $status['heading_hierarchy']);
        $this->assertIsInt($out['report']['score']);
    }

    public function test_clean_post_scores_100(): void
    {
        $id = $this->post('<h1>Title</h1><h2>Section</h2><img src="a.jpg" alt="Described">');

        $out = (new Analyze_Accessibility())->handle(['post_id' => $id]);

        $this->assertSame(100, $out['report']['score']);
    }

    public function test_missing_post_id_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Analyze_Accessibility())->handle([]);
    }

    private function make_ability(): Ability
    {
        return new Ability(
            'wpmcp/analyze-accessibility',
            'pro',
            'Scan a post for common WCAG issues.',
            [
                'type'       => 'object',
                'properties' => ['post_id' => ['type' => 'integer']],
                'required'   => ['post_id'],
            ],
            [new Analyze_Accessibility(), 'handle'],
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
        $this->assertContains('wpmcp/analyze-accessibility', $names);
    }
}
