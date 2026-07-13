<?php

namespace WPMCP\Tests\Pro\Cli;

use WPMCP\MCP\{Ability, Registrar};
use WPMCP\Pro\Gate;
use WPMCP\Tools\Cli\Run_Wp_Cli;

/**
 * run-wp-cli (issue #44) is PRO tier: an advanced/dangerous site-operations
 * capability, matching the Elementor-deep-editing precedent for gating a
 * feature at 'pro' rather than 'free'. Mirrors
 * BuilderAbilitiesRegistrationTest/ElementorDeepAbilitiesRegistrationTest:
 * Plugin::boot() registers abilities once at wp_abilities_api_init, so this
 * builds the same Ability the boot path constructs and drives it through a
 * fresh Registrar directly.
 */
class RunWpCliAbilitiesRegistrationTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        Gate::set_pro_for_tests(null);
        parent::tearDown();
    }

    private function make_run_wp_cli_ability(): Ability
    {
        return new Ability(
            'wpmcp/run-wp-cli',
            'pro',
            'Run a guarded, allowlisted wp-cli subcommand.',
            [
                'type'       => 'object',
                'properties' => ['command' => ['type' => 'string']],
                'required'   => ['command'],
            ],
            [new Run_Wp_Cli(), 'handle'],
            'manage_options',
            'cli',
            'update'
        );
    }

    public function test_registrar_skips_run_wp_cli_when_free(): void
    {
        Gate::set_pro_for_tests(false);

        $registrar = new Registrar();
        $registrar->register($this->make_run_wp_cli_ability());

        $this->assertCount(0, $registrar->all());
    }

    public function test_registrar_keeps_run_wp_cli_when_pro(): void
    {
        Gate::set_pro_for_tests(true);

        $registrar = new Registrar();
        $registrar->register($this->make_run_wp_cli_ability());

        $names = array_map(fn($a) => $a->name, $registrar->all());
        $this->assertContains('wpmcp/run-wp-cli', $names);
    }
}
