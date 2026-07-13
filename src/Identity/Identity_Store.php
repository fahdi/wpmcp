<?php

namespace WPMCP\Identity;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * CRUD over a single wpmcp_identities option: a map of identity name =>
 * identity record. A record is { name, domains, operations, abilities, mode },
 * where domains/operations/abilities are string[] allowlists (an empty array
 * means "no restriction on this dimension") and mode is 'allow' (default) or
 * 'deny'. The identity name is the natural, caller-chosen unique key, so
 * there is no separate id sequence to keep deterministic (unlike
 * Backup_Job_Store, which needs one because jobs are anonymous).
 */
class Identity_Store
{
    public const OPTION = 'wpmcp_identities';

    private static function load(): array
    {
        $stored = get_option(self::OPTION, []);
        return is_array($stored) ? $stored : [];
    }

    private static function save(array $stored): void
    {
        update_option(self::OPTION, $stored);
    }

    /**
     * Create (or overwrite) the identity named $name. $fields may contain
     * 'domains', 'operations', 'abilities' (each string[], default []) and
     * 'mode' ('allow'|'deny', default 'allow'). Returns the stored record.
     */
    public static function create(string $name, array $fields): array
    {
        $record = [
            'name'       => $name,
            'domains'    => array_values(array_map('strval', $fields['domains'] ?? [])),
            'operations' => array_values(array_map('strval', $fields['operations'] ?? [])),
            'abilities'  => array_values(array_map('strval', $fields['abilities'] ?? [])),
            'mode'       => 'deny' === ($fields['mode'] ?? 'allow') ? 'deny' : 'allow',
        ];

        $stored          = self::load();
        $stored[ $name ] = $record;
        self::save($stored);

        return $record;
    }

    /** Fetch an identity by name, or null if it does not exist. */
    public static function get(string $name): ?array
    {
        $stored = self::load();
        return $stored[ $name ] ?? null;
    }

    /** @return array<int, array> all identities, in creation/insertion order. */
    public static function list(): array
    {
        return array_values(self::load());
    }

    /** Delete the identity named $name. Returns true if it existed, false otherwise. */
    public static function delete(string $name): bool
    {
        $stored = self::load();
        if (! isset($stored[ $name ])) {
            return false;
        }
        unset($stored[ $name ]);
        self::save($stored);
        return true;
    }
}
