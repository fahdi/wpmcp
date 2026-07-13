<?php

namespace WPMCP\Tests\Free\Meta;

use WPMCP\Tools\Meta\Get_Option;

class GetOptionTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        delete_option('wpmcp_test_option');
        parent::tearDown();
    }

    public function test_returns_an_option_value(): void
    {
        update_option('wpmcp_test_option', 'hello');

        $out = (new Get_Option())->handle(['name' => 'wpmcp_test_option']);

        $this->assertSame('wpmcp_test_option', $out['name']);
        $this->assertSame('hello', $out['value']);
    }

    public function test_requires_a_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Option())->handle([]);
    }

    public function test_refuses_a_denylisted_option(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Get_Option())->handle(['name' => 'auth_key']);
    }
}
