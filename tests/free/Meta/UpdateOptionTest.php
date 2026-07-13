<?php

namespace WPMCP\Tests\Free\Meta;

use WPMCP\Tools\Meta\Update_Option;
use WPMCP\Tools\Rollback_Operation;
use WPMCP\Safety\Snapshot_Store;

class UpdateOptionTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Snapshot_Store::install();
    }

    protected function tearDown(): void
    {
        delete_option('wpmcp_test_option');
        remove_all_filters('wpmcp_enable_option_write');
        parent::tearDown();
    }

    public function test_disabled_by_default(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Update_Option())->handle(['name' => 'wpmcp_test_option', 'value' => 'x']);
    }

    public function test_enabled_via_filter(): void
    {
        add_filter('wpmcp_enable_option_write', '__return_true');

        $out = (new Update_Option())->handle(['name' => 'wpmcp_test_option', 'value' => 'x']);

        $this->assertArrayHasKey('operation_id', $out);
        $this->assertSame('x', get_option('wpmcp_test_option'));
    }

    public function test_refuses_a_denylisted_option_even_when_enabled(): void
    {
        add_filter('wpmcp_enable_option_write', '__return_true');

        $this->expectException(\RuntimeException::class);
        (new Update_Option())->handle(['name' => 'siteurl', 'value' => 'https://evil.example']);
    }

    public function test_write_is_snapshotted_and_rollback_restores_prior_value(): void
    {
        add_filter('wpmcp_enable_option_write', '__return_true');
        update_option('wpmcp_test_option', 'original');

        $out = (new Update_Option())->handle(['name' => 'wpmcp_test_option', 'value' => 'mutated']);

        $this->assertNotNull(Snapshot_Store::get_by_operation($out['operation_id']));
        $this->assertSame('mutated', get_option('wpmcp_test_option'));

        $rolled_back = (new Rollback_Operation())->handle(['operation_id' => $out['operation_id']]);
        $this->assertTrue($rolled_back['restored']);

        $this->assertSame('original', get_option('wpmcp_test_option'));
    }
}
