<?php

namespace WPMCP\Governance;

use WPMCP\Identity\Identity_Context;
use WPMCP\Identity\Identity_Store;
use WPMCP\MCP\Ability;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Resolves whether an Ability should be registered/executable, layering six
 * checks: a stored decision and a filter for each of the three dimensions
 * (ability, domain, operation), most- to least-specific.
 *
 * Composition rule (AND-of-narrowing): every layer starts from "enabled" and
 * can only turn it off, never force it back on. Concretely, in order:
 *   1. stored ability-level decision (false disables, true is a no-op)
 *   2. wpmcp_ability_enabled filter
 *   3. stored domain-level decision (false disables, true is a no-op)
 *   4. wpmcp_domain_enabled filter
 *   5. stored operation-level decision (false disables, true is a no-op)
 *   6. wpmcp_operation_enabled filter
 * The check short-circuits to false the moment any layer disables the
 * ability. A stored "enabled=true" entry never overrides a disable coming
 * from any other layer (dimension or filter); it exists only so callers can
 * see an explicit state, and is otherwise identical to no entry at all.
 */
class Governance
{
    public const OPTION = 'wpmcp_governance_settings';

    public static function is_ability_enabled(Ability $a): bool
    {
        $stored = self::stored_settings();

        if (isset($stored['ability'][ $a->name ]) && false === $stored['ability'][ $a->name ]) {
            return false;
        }
        $enabled = apply_filters('wpmcp_ability_enabled', true, $a->name);
        if (! $enabled) {
            return false;
        }

        if (isset($stored['domain'][ $a->domain ]) && false === $stored['domain'][ $a->domain ]) {
            return false;
        }
        $enabled = apply_filters('wpmcp_domain_enabled', $enabled, $a->domain);
        if (! $enabled) {
            return false;
        }

        if (isset($stored['operation'][ $a->operation ]) && false === $stored['operation'][ $a->operation ]) {
            return false;
        }
        $enabled = apply_filters('wpmcp_operation_enabled', $enabled, $a->operation);

        return (bool) $enabled;
    }

    /** Explicitly enable or disable a single named ability. */
    public static function set_ability_toggle(string $name, bool $enabled): void
    {
        self::set_toggle('ability', $name, $enabled);
    }

    /** Explicitly enable or disable an entire domain. */
    public static function set_domain_toggle(string $domain, bool $enabled): void
    {
        self::set_toggle('domain', $domain, $enabled);
    }

    /** Explicitly enable or disable an entire operation across all domains/abilities. */
    public static function set_operation_toggle(string $operation, bool $enabled): void
    {
        self::set_toggle('operation', $operation, $enabled);
    }

    /** @return array<string, bool> stored ability name => enabled decisions. */
    public static function ability_toggles(): array
    {
        return self::stored_settings()['ability'];
    }

    /** @return array<string, bool> stored domain => enabled decisions. */
    public static function domain_toggles(): array
    {
        return self::stored_settings()['domain'];
    }

    /** @return array<string, bool> stored operation => enabled decisions. */
    public static function operation_toggles(): array
    {
        return self::stored_settings()['operation'];
    }

    /** Clear every stored toggle, restoring pure filter-based/default behavior. */
    public static function reset_for_tests(): void
    {
        delete_option(self::OPTION);
    }

    /**
     * Whether $a is usable under the currently active identity (see
     * Identity_Context::current()). This is an additional narrowing layer on
     * top of is_ability_enabled() and the caller's WordPress capability: it
     * can only take away, never grant.
     *
     *  - No identity active (current() is null): unrestricted, returns true.
     *  - Identity name not found in Identity_Store: default-deny, returns
     *    false. An unknown identity is treated as ambiguous/untrusted, not
     *    as "no restriction".
     *  - Identity found: each non-empty scope array (domains, operations,
     *    abilities) is an allowlist that $a must satisfy; empty arrays place
     *    no restriction on that dimension. All non-empty dimensions must
     *    match (AND), i.e. an identity restricted to domains=[content] AND
     *    operations=[read] only allows abilities that are both in the
     *    content domain and a read operation. mode='deny' inverts the match
     *    (an ability matching the given scope is the one that gets denied,
     *    everything else is allowed); mode='allow' (the default) is the
     *    ordinary allowlist behavior described above.
     */
    public static function is_within_identity_scope(Ability $a): bool
    {
        $current = Identity_Context::current();
        if (null === $current) {
            return true;
        }

        $identity = Identity_Store::get($current);
        if (null === $identity) {
            return false;
        }

        $matches = self::identity_matches($identity, $a);

        return 'deny' === $identity['mode'] ? ! $matches : $matches;
    }

    private static function identity_matches(array $identity, Ability $a): bool
    {
        if ($identity['domains'] && ! in_array($a->domain, $identity['domains'], true)) {
            return false;
        }
        if ($identity['operations'] && ! in_array($a->operation, $identity['operations'], true)) {
            return false;
        }
        if ($identity['abilities'] && ! in_array($a->name, $identity['abilities'], true)) {
            return false;
        }
        return true;
    }

    private static function set_toggle(string $dimension, string $key, bool $enabled): void
    {
        $stored                       = self::stored_settings();
        $stored[ $dimension ][ $key ] = $enabled;
        update_option(self::OPTION, $stored);
    }

    /** @return array{ability: array<string,bool>, domain: array<string,bool>, operation: array<string,bool>} */
    private static function stored_settings(): array
    {
        $stored = get_option(self::OPTION, []);
        if (! is_array($stored)) {
            $stored = [];
        }
        foreach (['ability', 'domain', 'operation'] as $dimension) {
            $stored[ $dimension ] = is_array($stored[ $dimension ] ?? null) ? $stored[ $dimension ] : [];
        }
        return $stored;
    }
}
