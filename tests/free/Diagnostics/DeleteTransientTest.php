<?php

namespace WPMCP\Tests\Free\Diagnostics;

use WPMCP\Tools\Diagnostics\Delete_Transient;

class DeleteTransientTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        delete_transient('wpmcp_diag_delete_me');
        parent::tearDown();
    }

    public function test_deletes_a_seeded_transient(): void
    {
        set_transient('wpmcp_diag_delete_me', 'x', HOUR_IN_SECONDS);
        $this->assertSame('x', get_transient('wpmcp_diag_delete_me'));

        $out = (new Delete_Transient())->handle(['name' => 'wpmcp_diag_delete_me']);

        $this->assertTrue($out['deleted']);
        $this->assertFalse(get_transient('wpmcp_diag_delete_me'));
    }

    public function test_errors_on_a_missing_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Delete_Transient())->handle([]);
    }

    public function test_errors_on_an_empty_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Delete_Transient())->handle(['name' => '']);
    }
}
