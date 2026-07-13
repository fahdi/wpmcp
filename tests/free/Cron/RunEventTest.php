<?php

namespace WPMCP\Tests\Free\Cron;

use WPMCP\Tools\Cron\Run_Event;

class RunEventTest extends \WP_UnitTestCase
{
    protected function tearDown(): void
    {
        wp_clear_scheduled_hook('wpmcp_cron_test_event');
        remove_all_filters('wpmcp_enable_run_cron_event');
        remove_all_actions('wpmcp_cron_test_event');
        parent::tearDown();
    }

    public function test_disabled_by_default(): void
    {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'wpmcp_cron_test_event');

        $this->expectException(\RuntimeException::class);
        (new Run_Event())->handle(['hook' => 'wpmcp_cron_test_event']);
    }

    public function test_fires_a_scheduled_hook_when_enabled(): void
    {
        add_filter('wpmcp_enable_run_cron_event', '__return_true');
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'wpmcp_cron_test_event');

        $fired = new \stdClass();
        $fired->count = 0;
        add_action('wpmcp_cron_test_event', function () use ($fired): void {
            $fired->count++;
        });

        $out = (new Run_Event())->handle(['hook' => 'wpmcp_cron_test_event']);

        $this->assertSame(1, $fired->count);
        $this->assertTrue($out['ran']);
    }

    public function test_replays_stored_event_args(): void
    {
        add_filter('wpmcp_enable_run_cron_event', '__return_true');
        wp_schedule_single_event(time() + HOUR_IN_SECONDS, 'wpmcp_cron_test_event', ['stored-value']);

        $received = new \stdClass();
        $received->value = null;
        add_action('wpmcp_cron_test_event', function ($value) use ($received): void {
            $received->value = $value;
        });

        (new Run_Event())->handle([
            'hook' => 'wpmcp_cron_test_event',
            'args' => ['caller-supplied-value'],
        ]);

        $this->assertSame('stored-value', $received->value);
    }

    public function test_refuses_a_hook_that_is_not_scheduled(): void
    {
        add_filter('wpmcp_enable_run_cron_event', '__return_true');

        $this->expectException(\RuntimeException::class);
        (new Run_Event())->handle(['hook' => 'wpmcp_cron_test_event']);
    }

    public function test_requires_a_hook(): void
    {
        add_filter('wpmcp_enable_run_cron_event', '__return_true');

        $this->expectException(\InvalidArgumentException::class);
        (new Run_Event())->handle([]);
    }
}
