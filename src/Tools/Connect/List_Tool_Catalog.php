<?php

namespace WPMCP\Tools\Connect;

use WPMCP\MCP\Registrar;
use WPMCP\Plugin;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: return the catalog of wpmcp abilities currently registered on
 * this site, grouped by domain.
 *
 * Defaults to Plugin::instance()->registrar(), the same shared Registrar
 * every ability in Plugin::boot() is registered into, so the catalog always
 * reflects what is actually available on this site right now (a free-tier
 * site never sees pro abilities here, matching Registrar's own pro-gating).
 * A caller (namely this class's own test suite) may inject a different
 * Registrar to inspect a hand-built ability set, e.g. one built with
 * Gate::set_pro_for_tests(true) to exercise a pro-tier entry.
 *
 * Each entry reports only metadata already public on the Ability object
 * (name, tier, operation, capability, read/destructive hints); it never
 * invokes a tool's handler, so this has nothing to snapshot or roll back.
 */
class List_Tool_Catalog
{
    public function handle(array $args, ?Registrar $registrar = null): array
    {
        $registrar    = $registrar ?? Plugin::instance()->registrar();
        $domain_filter = isset($args['domain']) ? (string) $args['domain'] : '';
        $tier_filter   = isset($args['tier']) ? (string) $args['tier'] : '';

        $domains = [];

        foreach ($registrar->all() as $ability) {
            if ('' !== $domain_filter && $ability->domain !== $domain_filter) {
                continue;
            }
            if ('' !== $tier_filter && $ability->tier !== $tier_filter) {
                continue;
            }

            $domains[ $ability->domain ][] = [
                'name'             => $ability->name,
                'tier'             => $ability->tier,
                'operation'        => $ability->operation,
                'capability'       => $ability->capability,
                'read_only_hint'   => $ability->read_only_hint,
                'destructive_hint' => $ability->destructive_hint,
            ];
        }

        ksort($domains);

        $summary = [];
        foreach ($domains as $domain => $entries) {
            $summary[ $domain ] = count($entries);
        }

        return [
            'domains' => $domains,
            'summary' => $summary,
            'total'   => array_sum($summary),
        ];
    }
}
