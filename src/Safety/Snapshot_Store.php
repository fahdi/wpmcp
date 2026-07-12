<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols -- ABSPATH guard is an intentional side effect.
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps -- WP-style snake_case class name is intentional (matches brief's public interface).
// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WP-style snake_case method names are intentional (matches brief's public interface).

namespace WPMCP\Safety;

if (! defined('ABSPATH')) {
    exit;
}

class Snapshot_Store
{
    public static function table_name(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'wpmcp_snapshots';
    }

    public static function install(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table   = self::table_name();
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            operation_id CHAR(36) NOT NULL,
            session_id CHAR(36) NOT NULL,
            object_type VARCHAR(32) NOT NULL,
            object_id BIGINT UNSIGNED NOT NULL,
            tool_name VARCHAR(64) NOT NULL,
            args_hash CHAR(64) NOT NULL,
            before_blob LONGBLOB NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY operation_id (operation_id),
            KEY session_id (session_id)
        ) {$charset};");
    }
}
