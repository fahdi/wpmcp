<?php

namespace WPMCP\Tools\Analytics;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: report which analytics provider (if any) is active and whether
 * it appears connected, via Analytics_Adapter::connection_status(). No args.
 * Always registered, even when no provider is configured, so a caller can
 * discover state before using the rest of the analytics tool group (mirrors
 * wpmcp/is-multisite and get-seo-status).
 *
 * Unlike the other four analytics tools, this never returns a WP_Error: the
 * whole point of this tool is to safely report "not connected" as data,
 * not as a failure.
 */
class Get_Analytics_Connection_Status
{
    public function handle(array $args): array
    {
        return Analytics_Adapter::connection_status();
    }
}
