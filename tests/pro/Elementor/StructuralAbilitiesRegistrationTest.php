<?php

namespace WPMCP\Tests\Pro\Elementor;

use WPMCP\MCP\{Ability, Registrar};
use WPMCP\Pro\Gate;
use WPMCP\Tools\Elementor\Add_Container;
use WPMCP\Tools\Elementor\Batch_Update;
use WPMCP\Tools\Elementor\Duplicate_Element;
use WPMCP\Tools\Elementor\Find_Element;
use WPMCP\Tools\Elementor\Reorder_Elements;
use WPMCP\Tools\Elementor\Set_Element_Label;
use WPMCP\Tools\Elementor\Update_Container;
use WPMCP\Tools\Elementor\Update_Page_Settings;

/**
 * Pro gating for the structural editing suite (issue #58): every tool is a
 * 'pro' tier ability, so Registrar must drop each one on a free-tier site
 * and keep each one on a pro-tier site. Mirrors the fresh-Registrar
 * approach of ElementorDeepAbilitiesRegistrationTest (Plugin::boot()
 * registers once, so the shared instance cannot be re-exercised per-test).
 */
class StructuralAbilitiesRegistrationTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        Gate::set_pro_for_tests(null);
        parent::tearDown();
    }

    /** @return array<string, callable> ability name => handler. */
    private function suite(): array
    {
        return [
            'wpmcp/add-container'        => [new Add_Container(), 'handle'],
            'wpmcp/update-container'     => [new Update_Container(), 'handle'],
            'wpmcp/batch-update'         => [new Batch_Update(), 'handle'],
            'wpmcp/reorder-elements'     => [new Reorder_Elements(), 'handle'],
            'wpmcp/duplicate-element'    => [new Duplicate_Element(), 'handle'],
            'wpmcp/set-element-label'    => [new Set_Element_Label(), 'handle'],
            'wpmcp/find-element'         => [new Find_Element(), 'handle'],
            'wpmcp/update-page-settings' => [new Update_Page_Settings(), 'handle'],
        ];
    }

    private function make_ability(string $name, callable $handler): Ability
    {
        return new Ability(
            $name,
            'pro',
            'Structural Elementor editing tool.',
            ['type' => 'object', 'properties' => ['post_id' => ['type' => 'integer']], 'required' => ['post_id']],
            $handler,
            'edit_posts',
            'elementor',
            'wpmcp/find-element' === $name ? 'read' : 'update'
        );
    }

    public function test_registrar_skips_every_structural_tool_when_free(): void
    {
        Gate::set_pro_for_tests(false);

        $registrar = new Registrar();
        foreach ($this->suite() as $name => $handler) {
            $registrar->register($this->make_ability($name, $handler));
        }

        $this->assertCount(0, $registrar->all());
    }

    public function test_registrar_keeps_every_structural_tool_when_pro(): void
    {
        Gate::set_pro_for_tests(true);

        $registrar = new Registrar();
        foreach ($this->suite() as $name => $handler) {
            $registrar->register($this->make_ability($name, $handler));
        }

        $names = array_map(fn ($a) => $a->name, $registrar->all());
        foreach (array_keys($this->suite()) as $name) {
            $this->assertContains($name, $names);
        }
    }

    public function test_live_registration_declares_the_suite_pro_tier(): void
    {
        Gate::set_pro_for_tests(true);

        $map = \WPMCP\Tests\Free\Platform\RegisteredAbilities::manifest_map();

        foreach (array_keys($this->suite()) as $name) {
            $this->assertArrayHasKey($name, $map, "{$name} must be registered by Plugin::register_abilities()");
            $this->assertSame('pro', $map[ $name ], "{$name} must be pro tier");
        }
    }
}
