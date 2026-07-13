<?php

namespace WPMCP\Tests\Free\Multisite;

use WPMCP\Tools\Multisite\Is_Multisite;

class IsMultisiteTest extends \WP_UnitTestCase
{
    public function test_reports_false_in_this_single_site_harness(): void
    {
        $out = (new Is_Multisite())->handle([]);

        $this->assertIsArray($out);
        $this->assertFalse($out['is_multisite']);
    }

    public function test_output_shape_is_a_plain_array_with_only_the_expected_key(): void
    {
        $out = (new Is_Multisite())->handle([]);

        $this->assertSame(['is_multisite'], array_keys($out));
    }
}
