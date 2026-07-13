<?php

namespace WPMCP\Maintenance;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Front-end enforcement for maintenance mode: when the wpmcp_maintenance
 * option has enabled=true, non-logged-in visitors and logged-in users who
 * lack manage_options are served a 503 response instead of the page.
 *
 * The decision (should_block) is kept separate from the side effect
 * (enforce, which sends headers/body and exits) so it can be unit tested
 * without terminating the test process. Only manage_options users are ever
 * blocked out: this is the same capability gate used to register the
 * maintenance tools themselves, so anyone who can turn maintenance mode on
 * or off can also still reach the site while it is on.
 */
class Maintenance_Guard
{
    public function should_block(): bool
    {
        $option = get_option('wpmcp_maintenance');
        if (! is_array($option) || empty($option['enabled'])) {
            return false;
        }

        if (is_user_logged_in() && current_user_can('manage_options')) {
            return false;
        }

        return true;
    }

    /**
     * Hooked to template_redirect. Sends a 503 with the configured message
     * and Retry-After header, then terminates the request so no further
     * template output reaches a blocked visitor.
     */
    public function enforce(): void
    {
        if (! $this->should_block()) {
            return;
        }

        $option      = get_option('wpmcp_maintenance');
        $message     = is_array($option) ? (string) ($option['message'] ?? '') : '';
        $retry_after = is_array($option) ? (int) ($option['retry_after'] ?? 0) : 0;

        status_header(503);
        nocache_headers();
        if ($retry_after > 0) {
            header('Retry-After: ' . $retry_after);
        }

        echo esc_html($message);
        exit;
    }
}
