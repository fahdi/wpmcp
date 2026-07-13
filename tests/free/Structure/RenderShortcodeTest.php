<?php

namespace WPMCP\Tests\Free\Structure;

use WPMCP\Tools\Structure\Render_Shortcode;

class RenderShortcodeTest extends \WP_UnitTestCase
{
    public function tearDown(): void
    {
        remove_shortcode('wpmcp_test');
        parent::tearDown();
    }

    public function test_renders_a_registered_shortcode(): void
    {
        add_shortcode('wpmcp_test', function ($atts) {
            $atts = shortcode_atts(['name' => 'world'], $atts);
            return 'hello ' . $atts['name'];
        });

        $out = (new Render_Shortcode())->handle(['shortcode' => '[wpmcp_test name="Sam"]']);

        $this->assertSame('hello Sam', $out['html']);
    }

    public function test_throws_when_shortcode_arg_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Render_Shortcode())->handle([]);
    }
}
