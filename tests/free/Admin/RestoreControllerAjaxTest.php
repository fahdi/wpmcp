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
     * The ajax test harness runs a full admin-ajax request cycle, which can
     * trigger WordPress core's wp_version_check() to api.wordpress.org.
     * That is a real outbound HTTP call, and it can flake or fail in a CI
     * environment without reliable network access. Preempt only that
     * specific request with a canned, harmless response so the test never
     * makes a real network call; every other HTTP request is left alone.
     */
    public function stub_version_check($preempt, $parsed_args, $url)
    {
        if (false === strpos($url, 'api.wordpress.org') || false === strpos($url, 'version-check')) {
            return $preempt;
        }

        return [
            'headers'  => [],
            'body'     => wp_json_encode(['offers' => []]),
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
}
