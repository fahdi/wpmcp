<?php

namespace WPMCP\Tests\Pro;

use WPMCP\Pro\Gate;
use WPMCP\MCP\{Registrar, Ability};

class GateTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        Gate::set_pro_for_tests(null);
        parent::tearDown();
    }

    public function test_free_defaults(): void
    {
        Gate::set_pro_for_tests(false);
        $this->assertFalse(Gate::is_pro());
        $this->assertSame(20, Gate::history_limit());
    }

    public function test_registrar_skips_pro_when_free(): void
    {
        Gate::set_pro_for_tests(false);
        $r = new Registrar();
        $r->register(new Ability('wpmcp/elementor-deep', 'pro', 'Pro', [], fn($a) => []));
        $this->assertCount(0, $r->all());
    }

    public function test_pro_unlocks(): void
    {
        Gate::set_pro_for_tests(true);
        $this->assertTrue(Gate::is_pro());
        $this->assertGreaterThan(1000000, Gate::history_limit());
    }

    public function test_is_pro_falls_back_safely_without_freemius_sdk(): void
    {
        // No override set (real default path): with the SDK absent, wpmcp_fs()
        // does not exist, so is_pro() must short-circuit to false, not fatal.
        $this->assertFalse(function_exists('wpmcp_fs'));
        $this->assertFalse(Gate::is_pro());
    }
}
