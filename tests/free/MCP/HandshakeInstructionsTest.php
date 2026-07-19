<?php

namespace WPMCP\Tests\Free\MCP;

use WPMCP\MCP\Handshake_Instructions;

/**
 * Handshake context injection (issue #80): the instructions string served in
 * the MCP initialize response merges an admin-editable option with a bounded
 * auto-generated site summary (site name, active builder, safety-model
 * one-liner) derived from the same data get-site-context reports.
 */
class HandshakeInstructionsTest extends \WP_UnitTestCase
{
    public static function wpSetUpBeforeClass(): void
    {
        // wp_abilities_api_init fires lazily on first registry access; the
        // real wpmcp/get-site-context ability (whose permission gate the
        // handshake summary reuses) is only registered once it has fired.
        if (0 === did_action('wp_abilities_api_init')) {
            do_action('wp_abilities_api_init');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        delete_option(Handshake_Instructions::OPTION);

        // The auto-summary is gated on the same authorization as the
        // get-site-context tool, so most tests run as a user who could call
        // that tool themselves (edit_posts).
        $editor = self::factory()->user->create(['role' => 'editor']);
        wp_set_current_user($editor);
    }

    protected function tearDown(): void
    {
        delete_option(Handshake_Instructions::OPTION);
        parent::tearDown();
    }

    public function test_auto_summary_contains_site_name_builder_and_safety_one_liner(): void
    {
        update_option('blogname', 'Handshake Test Site');

        $summary = (new Handshake_Instructions())->auto_summary();

        $this->assertStringContainsString('Handshake Test Site', $summary);
        $this->assertStringContainsString((new Handshake_Instructions())->active_builder(), $summary);
        $this->assertStringContainsString('snapshotted', $summary);
        $this->assertStringContainsString('rollback', $summary);
    }

    public function test_active_builder_matches_the_environment(): void
    {
        $builder = (new Handshake_Instructions())->active_builder();

        $expected = class_exists('\\Elementor\\Plugin') ? 'elementor' : 'gutenberg';
        $this->assertSame($expected, $builder);
    }

    public function test_empty_setting_degrades_to_auto_summary_only(): void
    {
        $instructions = new Handshake_Instructions();

        $this->assertSame($instructions->auto_summary(), $instructions->build());
    }

    public function test_admin_authored_text_appears_verbatim_ahead_of_the_auto_summary(): void
    {
        $admin_text = "Prefer British English.\nNever publish without asking; save drafts only.";
        update_option(Handshake_Instructions::OPTION, $admin_text);

        $instructions = new Handshake_Instructions();
        $built        = $instructions->build();

        $this->assertStringContainsString($admin_text, $built);
        $this->assertStringContainsString($instructions->auto_summary(), $built);
        $this->assertLessThan(
            strpos($built, 'snapshotted'),
            strpos($built, 'Prefer British English.'),
            'Admin-authored text must come before the auto-generated summary.'
        );
    }

    public function test_whitespace_only_setting_degrades_to_auto_summary_only(): void
    {
        update_option(Handshake_Instructions::OPTION, "   \n\t  ");

        $instructions = new Handshake_Instructions();

        $this->assertSame($instructions->auto_summary(), $instructions->build());
    }
}
