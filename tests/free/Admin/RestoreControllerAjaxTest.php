<?php

namespace WPMCP\Tests\Free\Admin;

use WPMCP\Tools\Update_Blocks;
use WPMCP\Safety\Snapshot_Store;

/**
 * @group ajax
 */
class RestoreControllerAjaxTest extends \WP_Ajax_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Snapshot_Store::install();
        add_filter('pre_http_request', [$this, 'stub_version_check'], 10, 3);
    }

    protected function tearDown(): void
    {
        remove_filter('pre_http_request', [$this, 'stub_version_check'], 10);
        parent::tearDown();
    }

    /**
     * The ajax test harness runs a full admin-ajax request cycle, which fires
     * admin_init and with it core's _maybe_update_core(), _maybe_update_plugins(),
     * and _maybe_update_themes(). Whenever the update_core/update_plugins/
     * update_themes site transients are missing or stale, those helpers call
     * wp_version_check()/wp_update_plugins()/wp_update_themes(), each a real
     * outbound HTTP call to api.wordpress.org (core hits .../core/version-check/,
     * plugins and themes hit .../plugins/update-check/ and .../themes/update-check/).
     * Whether the transients are fresh enough to skip the call depends on what
     * other tests ran before this one in the same process, so left unstubbed
     * this test intermittently makes a real network call and errors when the
     * sandbox has no outbound network access. Preempt every api.wordpress.org
     * *-check request with a 200 response and an empty body: core treats a
     * response body that fails to decode into the expected shape as "nothing
     * to update" and returns early without writing a transient. A WP_Error
     * does NOT work here: when SSL is available core treats a WP_Error as a
     * possible SSL negotiation failure, logs a wp_trigger_error() warning,
     * and retries over plain HTTP, which this test environment surfaces as a
     * test error. An empty 200 body has no such retry path in any of the
     * three callers, so it is the only shape safe for all of them.
     */
    public function stub_version_check($preempt, $parsed_args, $url)
    {
        if (false === strpos($url, 'api.wordpress.org') || false === strpos($url, '-check')) {
            return $preempt;
        }

        return [
            'headers'  => [],
            'body'     => '',
            'response' => ['code' => 200, 'message' => 'OK'],
            'cookies'  => [],
            'filename' => null,
        ];
    }

    /**
     * Sets up a page with an original body, mutates it via Update_Blocks (which
     * snapshots the pre-mutation state), and returns [post_id, operation_id].
     */
    private function create_restorable_operation(): array
    {
        $id = self::factory()->post->create(['post_type' => 'page', 'post_content' => 'V0']);
        $op = (new Update_Blocks())->handle([
            'id'         => $id,
            'blocks'     => '<!-- wp:paragraph --><p>V1</p><!-- /wp:paragraph -->',
            'session_id' => 's',
        ]);

        return [$id, $op['operation_id']];
    }

    public function test_rejects_without_capability_or_nonce_and_does_not_restore(): void
    {
        [$id, $operation_id] = $this->create_restorable_operation();

        // Subscriber lacks edit_posts; also omit any nonce.
        $this->_setRole('subscriber');
        $_POST['operation_id'] = $operation_id;
        unset($_POST['nonce']);

        try {
            $this->_handleAjax('wpmcp_restore');
            $this->fail('Expected wp_send_json_error to terminate execution via wp_die.');
        } catch (\WPAjaxDieContinueException $e) {
            // wp_send_json_error() echoes a JSON body before calling wp_die(),
            // so the ajax test harness treats it as a "continue" die (there was output).
        } catch (\WPAjaxDieStopException $e) {
            // Also acceptable: some environments may short-circuit before any output.
        }

        $response = json_decode($this->_last_response, true);
        $this->assertIsArray($response, 'Expected a JSON error response body.');
        $this->assertFalse($response['success']);

        // Confirm restore never actually ran: content is still the mutated V1, not reverted to V0.
        $this->assertSame(
            '<!-- wp:paragraph --><p>V1</p><!-- /wp:paragraph -->',
            get_post($id)->post_content
        );
    }

    public function test_rejects_with_capability_but_invalid_nonce_and_does_not_restore(): void
    {
        [$id, $operation_id] = $this->create_restorable_operation();

        // Administrator HAS manage_options, but the nonce is bogus.
        $this->_setRole('administrator');
        $_POST['operation_id'] = $operation_id;
        $_POST['nonce']        = 'not-a-valid-nonce';

        try {
            $this->_handleAjax('wpmcp_restore');
            $this->fail('Expected wp_send_json_error to terminate execution via wp_die.');
        } catch (\WPAjaxDieContinueException $e) {
        } catch (\WPAjaxDieStopException $e) {
        }

        $response = json_decode($this->_last_response, true);
        $this->assertIsArray($response, 'Expected a JSON error response body.');
        $this->assertFalse($response['success']);

        $this->assertSame(
            '<!-- wp:paragraph --><p>V1</p><!-- /wp:paragraph -->',
            get_post($id)->post_content
        );
    }

    /**
     * Restore rolls back ALL users' site-wide agent mutations, so it is gated
     * at manage_options, not the weaker edit_posts. An editor holds edit_posts
     * but not manage_options, so even with a valid nonce the restore is refused
     * and the content stays mutated.
     */
    public function test_rejects_editor_without_manage_options_even_with_valid_nonce(): void
    {
        [$id, $operation_id] = $this->create_restorable_operation();

        $this->_setRole('editor');
        $_POST['operation_id'] = $operation_id;
        $_POST['nonce']        = wp_create_nonce('wpmcp_restore');

        try {
            $this->_handleAjax('wpmcp_restore');
            $this->fail('Expected wp_send_json_error to terminate execution via wp_die.');
        } catch (\WPAjaxDieContinueException $e) {
        } catch (\WPAjaxDieStopException $e) {
        }

        $response = json_decode($this->_last_response, true);
        $this->assertIsArray($response, 'Expected a JSON error response body.');
        $this->assertFalse($response['success']);

        $this->assertSame(
            '<!-- wp:paragraph --><p>V1</p><!-- /wp:paragraph -->',
            get_post($id)->post_content
        );
    }

    public function test_succeeds_with_capability_and_valid_nonce_and_reverts_content(): void
    {
        [$id, $operation_id] = $this->create_restorable_operation();

        $this->_setRole('administrator');
        $_POST['operation_id'] = $operation_id;
        $_POST['nonce']        = wp_create_nonce('wpmcp_restore');

        try {
            $this->_handleAjax('wpmcp_restore');
            $this->fail('Expected wp_send_json_success to terminate execution via wp_die.');
        } catch (\WPAjaxDieContinueException $e) {
        } catch (\WPAjaxDieStopException $e) {
        }

        $response = json_decode($this->_last_response, true);
        $this->assertIsArray($response, 'Expected a JSON success response body.');
        $this->assertTrue($response['success']);
        $this->assertTrue($response['data']['restored']);

        $this->assertSame('V0', get_post($id)->post_content);
    }

    /**
     * stub_version_check() must preempt every api.wordpress.org *-check
     * endpoint admin_init can trigger, not only core's version-check: plugin
     * and theme update checks use "update-check" rather than "version-check"
     * in their path and were previously left to hit the real network.
     */
    public function test_stub_intercepts_core_plugin_and_theme_update_check_urls(): void
    {
        foreach ([
            'https://api.wordpress.org/core/version-check/1.7/',
            'https://api.wordpress.org/plugins/update-check/1.1/',
            'https://api.wordpress.org/themes/update-check/1.1/',
        ] as $url) {
            $result = $this->stub_version_check(false, [], $url);

            $this->assertIsArray($result, "Expected {$url} to be intercepted with a canned response.");
            $this->assertSame(200, $result['response']['code']);
            $this->assertSame('', $result['body']);
        }
    }

    /**
     * A non-wordpress.org host must never be intercepted by this stub: it
     * only exists to preempt the specific known-safe *-check endpoints.
     */
    public function test_stub_leaves_unrelated_hosts_untouched(): void
    {
        $preempt = false;

        $result = $this->stub_version_check($preempt, [], 'https://example.com/some-check/');

        $this->assertFalse($result, 'A non-wordpress.org host must pass the original $preempt value through.');
    }
}
