<?php

namespace WPMCP\Tests\Free\Identity;

use WPMCP\Identity\Identity_Context;

class IdentityContextTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        Identity_Context::set_current_for_tests(null);
        remove_all_filters('wpmcp_current_identity');
        parent::tearDown();
    }

    public function test_current_is_null_by_default(): void
    {
        $this->assertNull(Identity_Context::current());
    }

    public function test_set_current_for_tests_overrides_the_current_identity(): void
    {
        Identity_Context::set_current_for_tests('editor-bot');

        $this->assertSame('editor-bot', Identity_Context::current());
    }

    public function test_falls_back_to_wpmcp_current_identity_filter_when_no_test_override(): void
    {
        add_filter('wpmcp_current_identity', function () {
            return 'support-agent';
        });

        $this->assertSame('support-agent', Identity_Context::current());
    }

    public function test_test_override_takes_precedence_over_the_filter(): void
    {
        add_filter('wpmcp_current_identity', function () {
            return 'support-agent';
        });
        Identity_Context::set_current_for_tests('editor-bot');

        $this->assertSame('editor-bot', Identity_Context::current());
    }
}
