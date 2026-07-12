<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols -- ABSPATH guard is an intentional side effect.

namespace WPMCP;

use WPMCP\Admin\History_Page;
use WPMCP\Admin\Restore_Controller;
use WPMCP\MCP\Ability;
use WPMCP\MCP\Registrar;
use WPMCP\Tools\Get_Page;
use WPMCP\Tools\Update_Blocks;
use WPMCP\Tools\List_Operations;
use WPMCP\Tools\Rollback_Operation;
use WPMCP\Tools\Rollback_Session;
use WPMCP\Tools\Content\List_Post_Types;
use WPMCP\Tools\Content\List_Taxonomies;
use WPMCP\Tools\Content\Create_Post;
use WPMCP\Tools\Content\Get_Post;
use WPMCP\Tools\Content\Update_Post;
use WPMCP\Tools\Content\Delete_Post;
use WPMCP\Tools\Content\List_Posts;
use WPMCP\Tools\Content\Set_Post_Terms;
use WPMCP\Tools\Media\Get_Media;
use WPMCP\Tools\Media\Update_Media;
use WPMCP\Tools\Media\Delete_Media;
use WPMCP\Tools\Media\Sideload_Image;
use WPMCP\Tools\Settings\Get_Settings;
use WPMCP\Tools\Settings\Update_Settings;
use WPMCP\Tools\Users\List_Users;
use WPMCP\Tools\Users\Get_User;
use WPMCP\Tools\Users\Create_User;
use WPMCP\Tools\Users\Update_User;
use WPMCP\Tools\Packages\List_Plugins;
use WPMCP\Tools\Packages\Activate_Plugin;
use WPMCP\Tools\Packages\Deactivate_Plugin;
use WPMCP\Tools\Packages\Install_Plugin;
use WPMCP\Tools\Packages\Update_Plugin;
use WPMCP\Tools\Packages\Delete_Plugin;
use WPMCP\Tools\Packages\List_Themes;
use WPMCP\Tools\Packages\Switch_Theme;
use WPMCP\Tools\Packages\Install_Theme;
use WPMCP\Tools\Packages\Update_Theme;
use WPMCP\Tools\Packages\Delete_Theme;
use WPMCP\Tools\Database\List_Tables;
use WPMCP\Tools\Database\Describe_Table;
use WPMCP\Tools\Database\Query;
use WPMCP\Tools\Database\Insert_Row;
use WPMCP\Tools\Database\Update_Rows;
use WPMCP\Tools\Database\Delete_Rows;

if (! defined('ABSPATH') && ! defined('WPMCP_TESTING')) {
    exit;
}

final class Plugin
{
    private static ?Plugin $instance = null;
    public static function instance(): Plugin
    {
        return self::$instance ??= new self();
    }
    private function __construct()
    {
    }
    public function boot(): void
    {
        if (function_exists('register_activation_hook') && defined('WPMCP_FILE')) {
            register_activation_hook(WPMCP_FILE, [Activator::class, 'activate']);
        }
        if (function_exists('add_action')) {
            $hook = function_exists('wp_register_ability') ? 'wp_abilities_api_init' : 'init';
            add_action($hook, [$this, 'register_abilities']);
            if (function_exists('wp_register_ability_category')) {
                add_action('wp_abilities_api_categories_init', [$this, 'register_ability_category']);
            }
            add_action('admin_menu', [$this, 'register_admin_menu']);
            add_action('wp_ajax_wpmcp_restore', [new Restore_Controller(), 'handle']);
        }
    }

    /**
     * The Abilities API (WP 6.9+) requires every ability to belong to a
     * registered category before wp_register_ability() will accept it.
     * Categories must be registered on their own wp_abilities_api_categories_init
     * hook, separate from wp_abilities_api_init.
     */
    public function register_ability_category(): void
    {
        wp_register_ability_category('wpmcp', [
            'label'       => 'wpmcp',
            'description' => 'Abilities provided by the wpmcp plugin.',
        ]);
    }

    public function register_admin_menu(): void
    {
        add_menu_page(
            'wpmcp',
            'wpmcp',
            'edit_posts',
            'wpmcp',
            [new History_Page(), 'render']
        );
    }

    public function register_abilities(): void
    {
        $registrar          = new Registrar();
        $get_page           = new Get_Page();
        $update_blocks      = new Update_Blocks();
        $list_operations    = new List_Operations();
        $rollback_operation = new Rollback_Operation();
        $rollback_session   = new Rollback_Session();
        $registrar->register(new Ability(
            'wpmcp/get-page',
            'free',
            'Read a page',
            [
                'type'       => 'object',
                'properties' => [
                    'id' => [ 'type' => 'integer' ],
                ],
                'required'   => [ 'id' ],
            ],
            [$get_page, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/update-blocks',
            'free',
            'Update a page\'s block content',
            [
                'type'       => 'object',
                'properties' => [
                    'id'         => [ 'type' => 'integer' ],
                    'blocks'     => [ 'type' => 'string' ],
                    'session_id' => [ 'type' => 'string' ],
                ],
                'required'   => [ 'id', 'blocks' ],
            ],
            [$update_blocks, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/list-operations',
            'free',
            'List recent safety snapshot operations',
            [
                'type'       => 'object',
                'properties' => [
                    'limit' => [ 'type' => 'integer' ],
                ],
            ],
            [$list_operations, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/rollback-operation',
            'free',
            'Undo a single operation by restoring its pre-change snapshot',
            [
                'type'       => 'object',
                'properties' => [
                    'operation_id' => [ 'type' => 'string' ],
                ],
                'required'   => [ 'operation_id' ],
            ],
            [$rollback_operation, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/rollback-session',
            'free',
            'Undo all operations from a session by restoring each object\'s pre-session snapshot',
            [
                'type'       => 'object',
                'properties' => [
                    'session_id' => [ 'type' => 'string' ],
                ],
                'required'   => [ 'session_id' ],
            ],
            [$rollback_session, 'handle']
        ));

        $list_post_types = new List_Post_Types();
        $list_taxonomies = new List_Taxonomies();
        $create_post     = new Create_Post();
        $get_post        = new Get_Post();
        $update_post     = new Update_Post();
        $delete_post     = new Delete_Post();
        $list_posts      = new List_Posts();
        $set_post_terms  = new Set_Post_Terms();

        $registrar->register(new Ability(
            'wpmcp/list-post-types',
            'free',
            'List registered post types (posts, pages, custom post types)',
            [
                'type'       => 'object',
                'properties' => [
                    'public_only' => [ 'type' => 'boolean' ],
                ],
            ],
            [$list_post_types, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/list-taxonomies',
            'free',
            'List registered taxonomies (categories, tags, custom taxonomies)',
            [
                'type'       => 'object',
                'properties' => [
                    'post_type' => [ 'type' => 'string' ],
                ],
            ],
            [$list_taxonomies, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/create-post',
            'free',
            'Create a post, page, or custom post type',
            [
                'type'       => 'object',
                'properties' => [
                    'post_type' => [ 'type' => 'string' ],
                    'title'     => [ 'type' => 'string' ],
                    'content'   => [ 'type' => 'string' ],
                    'excerpt'   => [ 'type' => 'string' ],
                    'status'    => [ 'type' => 'string' ],
                    'slug'      => [ 'type' => 'string' ],
                    'parent'    => [ 'type' => 'integer' ],
                    'terms'     => [ 'type' => 'object' ],
                    'meta'      => [ 'type' => 'object' ],
                ],
            ],
            [$create_post, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/get-post',
            'free',
            'Read a single post, page, or custom post type',
            [
                'type'       => 'object',
                'properties' => [
                    'post_id' => [ 'type' => 'integer' ],
                ],
                'required'   => [ 'post_id' ],
            ],
            [$get_post, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/update-post',
            'free',
            'Partially update a post, page, or custom post type',
            [
                'type'       => 'object',
                'properties' => [
                    'post_id'        => [ 'type' => 'integer' ],
                    'title'          => [ 'type' => 'string' ],
                    'content'        => [ 'type' => 'string' ],
                    'excerpt'        => [ 'type' => 'string' ],
                    'status'         => [ 'type' => 'string' ],
                    'slug'           => [ 'type' => 'string' ],
                    'parent'         => [ 'type' => 'integer' ],
                    'terms'          => [ 'type' => 'object' ],
                    'terms_mode'     => [ 'type' => 'string' ],
                    'meta'           => [ 'type' => 'object' ],
                    'featured_image' => [ 'type' => [ 'object', 'null' ] ],
                    'session_id'     => [ 'type' => 'string' ],
                ],
                'required'   => [ 'post_id' ],
            ],
            [$update_post, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/delete-post',
            'free',
            'Delete a post, page, or custom post type (trash by default, force for permanent)',
            [
                'type'       => 'object',
                'properties' => [
                    'post_id'    => [ 'type' => 'integer' ],
                    'force'      => [ 'type' => 'boolean' ],
                    'session_id' => [ 'type' => 'string' ],
                ],
                'required'   => [ 'post_id' ],
            ],
            [$delete_post, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/list-posts',
            'free',
            'List/search posts, pages, or custom post types',
            [
                'type'       => 'object',
                'properties' => [
                    'post_type' => [ 'type' => 'string' ],
                    'status'    => [ 'type' => 'string' ],
                    'search'    => [ 'type' => 'string' ],
                    'author'    => [ 'type' => 'integer' ],
                    'parent'    => [ 'type' => 'integer' ],
                    'per_page'  => [ 'type' => 'integer' ],
                    'page'      => [ 'type' => 'integer' ],
                    'orderby'   => [ 'type' => 'string' ],
                    'order'     => [ 'type' => 'string' ],
                ],
            ],
            [$list_posts, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/set-post-terms',
            'free',
            'Assign taxonomy terms to a post (replace, append, or remove)',
            [
                'type'       => 'object',
                'properties' => [
                    'post_id'    => [ 'type' => 'integer' ],
                    'taxonomy'   => [ 'type' => 'string' ],
                    'terms'      => [ 'type' => 'array' ],
                    'mode'       => [ 'type' => 'string' ],
                    'session_id' => [ 'type' => 'string' ],
                ],
                'required'   => [ 'post_id', 'taxonomy', 'terms' ],
            ],
            [$set_post_terms, 'handle']
        ));

        $get_media      = new Get_Media();
        $update_media   = new Update_Media();
        $delete_media   = new Delete_Media();
        $sideload_image = new Sideload_Image();

        $registrar->register(new Ability(
            'wpmcp/get-media',
            'free',
            'Read full detail for a Media Library attachment: title, URL, every registered image size, dimensions, mime type, alt text, caption, and description',
            [
                'type'       => 'object',
                'properties' => [
                    'media_id' => [ 'type' => 'integer' ],
                ],
                'required'   => [ 'media_id' ],
            ],
            [$get_media, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/update-media',
            'free',
            'Update a Media Library attachment\'s title, alt text, caption, and/or description',
            [
                'type'       => 'object',
                'properties' => [
                    'media_id'    => [ 'type' => 'integer' ],
                    'title'       => [ 'type' => 'string' ],
                    'alt'         => [ 'type' => 'string' ],
                    'caption'     => [ 'type' => 'string' ],
                    'description' => [ 'type' => 'string' ],
                    'session_id'  => [ 'type' => 'string' ],
                ],
                'required'   => [ 'media_id' ],
            ],
            [$update_media, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/delete-media',
            'free',
            'Delete a Media Library attachment. Disabled by default (site must opt in via the wpmcp_enable_delete_media filter) and requires confirm:true. force:true permanently deletes, routed through the safety snapshot so it can be rolled back',
            [
                'type'       => 'object',
                'properties' => [
                    'media_id'   => [ 'type' => 'integer' ],
                    'confirm'    => [ 'type' => 'boolean' ],
                    'force'      => [ 'type' => 'boolean' ],
                    'session_id' => [ 'type' => 'string' ],
                ],
                'required'   => [ 'media_id', 'confirm' ],
            ],
            [$delete_media, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/sideload-image',
            'free',
            'Download an image from a URL and add it to the Media Library as a new attachment',
            [
                'type'       => 'object',
                'properties' => [
                    'url'         => [ 'type' => 'string' ],
                    'post_id'     => [ 'type' => 'integer' ],
                    'description' => [ 'type' => 'string' ],
                    'alt'         => [ 'type' => 'string' ],
                ],
                'required'   => [ 'url' ],
            ],
            [$sideload_image, 'handle']
        ));

        $get_settings    = new Get_Settings();
        $update_settings = new Update_Settings();

        $registrar->register(new Ability(
            'wpmcp/get-settings',
            'free',
            'Read WordPress site settings (general, reading, writing, discussion, media, permalinks), each with its group, type, and whether it is writable',
            [
                'type'       => 'object',
                'properties' => [
                    'group' => [ 'type' => 'string' ],
                    'keys'  => [ 'type' => 'array' ],
                ],
            ],
            [$get_settings, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/update-settings',
            'free',
            'Update WordPress site settings from a strict allowlist. Validates/coerces each value (enum, int range, bool), rejects unsafe permalink structures, skips read-only or non-allowlisted keys, and applies the valid subset even if some keys fail',
            [
                'type'       => 'object',
                'properties' => [
                    'settings' => [ 'type' => 'object' ],
                ],
                'required'   => [ 'settings' ],
            ],
            [$update_settings, 'handle']
        ));

        $list_users  = new List_Users();
        $get_user    = new Get_User();
        $create_user = new Create_User();
        $update_user = new Update_User();

        $registrar->register(new Ability(
            'wpmcp/list-users',
            'free',
            'List WordPress users as safe summary rows (id, username, display name, email, roles, registration date). Never returns password hashes or other secrets',
            [
                'type'       => 'object',
                'properties' => [
                    'role'     => [ 'type' => 'string' ],
                    'search'   => [ 'type' => 'string' ],
                    'per_page' => [ 'type' => 'integer' ],
                    'page'     => [ 'type' => 'integer' ],
                    'orderby'  => [ 'type' => 'string' ],
                    'order'    => [ 'type' => 'string' ],
                ],
            ],
            [$list_users, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/get-user',
            'free',
            'Read one user\'s profile detail, including an is_admin flag derived from live capabilities. Never returns the password hash',
            [
                'type'       => 'object',
                'properties' => [
                    'id' => [ 'type' => 'integer' ],
                ],
                'required'   => [ 'id' ],
            ],
            [$get_user, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/create-user',
            'free',
            'Create a new non-admin user. Auto-generates a strong password (never returned) and emails the new user so they can set their own. Rejects admin and unknown roles; defaults to subscriber',
            [
                'type'       => 'object',
                'properties' => [
                    'username'     => [ 'type' => 'string' ],
                    'email'        => [ 'type' => 'string' ],
                    'role'         => [ 'type' => 'string' ],
                    'display_name' => [ 'type' => 'string' ],
                    'first_name'   => [ 'type' => 'string' ],
                    'last_name'    => [ 'type' => 'string' ],
                ],
                'required'   => [ 'username', 'email' ],
            ],
            [$create_user, 'handle'],
            'create_users'
        ));
        $registrar->register(new Ability(
            'wpmcp/update-user',
            'free',
            'Update a non-admin user\'s profile fields (display name, email, url, nickname, first/last name, description). Refuses admin-capable users. Never changes role or password. Snapshotted so the change can be rolled back',
            [
                'type'       => 'object',
                'properties' => [
                    'id'           => [ 'type' => 'integer' ],
                    'display_name' => [ 'type' => 'string' ],
                    'email'        => [ 'type' => 'string' ],
                    'url'          => [ 'type' => 'string' ],
                    'nickname'     => [ 'type' => 'string' ],
                    'first_name'   => [ 'type' => 'string' ],
                    'last_name'    => [ 'type' => 'string' ],
                    'description'  => [ 'type' => 'string' ],
                    'session_id'   => [ 'type' => 'string' ],
                ],
                'required'   => [ 'id' ],
            ],
            [$update_user, 'handle'],
            'edit_users'
        ));

        $list_plugins      = new List_Plugins();
        $activate_plugin   = new Activate_Plugin();
        $deactivate_plugin = new Deactivate_Plugin();
        $install_plugin    = new Install_Plugin();
        $update_plugin     = new Update_Plugin();
        $delete_plugin     = new Delete_Plugin();
        $list_themes       = new List_Themes();
        $switch_theme      = new Switch_Theme();
        $install_theme     = new Install_Theme();
        $update_theme      = new Update_Theme();
        $delete_theme      = new Delete_Theme();

        $registrar->register(new Ability(
            'wpmcp/list-plugins',
            'free',
            'List installed plugins with active status, protected-package flag, and pending update info',
            [
                'type'       => 'object',
                'properties' => [],
            ],
            [$list_plugins, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/activate-plugin',
            'free',
            'Activate an installed plugin. Snapshots the prior active_plugins option so it can be rolled back',
            [
                'type'       => 'object',
                'properties' => [
                    'plugin'     => [ 'type' => 'string' ],
                    'session_id' => [ 'type' => 'string' ],
                ],
                'required'   => [ 'plugin' ],
            ],
            [$activate_plugin, 'handle'],
            'activate_plugins'
        ));
        $registrar->register(new Ability(
            'wpmcp/deactivate-plugin',
            'free',
            'Deactivate a plugin. Refuses protected packages (wpmcp, Elementor). Snapshots the prior active_plugins option so it can be rolled back',
            [
                'type'       => 'object',
                'properties' => [
                    'plugin'     => [ 'type' => 'string' ],
                    'session_id' => [ 'type' => 'string' ],
                ],
                'required'   => [ 'plugin' ],
            ],
            [$deactivate_plugin, 'handle'],
            'activate_plugins'
        ));
        $registrar->register(new Ability(
            'wpmcp/install-plugin',
            'free',
            'Install a plugin from wordpress.org by slug, optionally activating it. Additive only; nothing to roll back',
            [
                'type'       => 'object',
                'properties' => [
                    'slug'     => [ 'type' => 'string' ],
                    'activate' => [ 'type' => 'boolean' ],
                ],
                'required'   => [ 'slug' ],
            ],
            [$install_plugin, 'handle'],
            'install_plugins'
        ));
        $registrar->register(new Ability(
            'wpmcp/update-plugin',
            'free',
            'Update an installed plugin to the latest wordpress.org version. Disabled by default (wpmcp_enable_update_plugin filter) and requires confirm:true. File changes are not rollback-able',
            [
                'type'       => 'object',
                'properties' => [
                    'plugin'  => [ 'type' => 'string' ],
                    'confirm' => [ 'type' => 'boolean' ],
                ],
                'required'   => [ 'plugin', 'confirm' ],
            ],
            [$update_plugin, 'handle'],
            'update_plugins'
        ));
        $registrar->register(new Ability(
            'wpmcp/delete-plugin',
            'free',
            'Permanently delete an installed plugin\'s files. Disabled by default (wpmcp_enable_delete_plugin filter) and requires confirm:true. Refuses protected or active plugins. Not rollback-able',
            [
                'type'       => 'object',
                'properties' => [
                    'plugin'  => [ 'type' => 'string' ],
                    'confirm' => [ 'type' => 'boolean' ],
                ],
                'required'   => [ 'plugin', 'confirm' ],
            ],
            [$delete_plugin, 'handle'],
            'delete_plugins'
        ));

        $registrar->register(new Ability(
            'wpmcp/list-themes',
            'free',
            'List installed themes with active status, parent theme, and pending update info',
            [
                'type'       => 'object',
                'properties' => [],
            ],
            [$list_themes, 'handle']
        ));
        $registrar->register(new Ability(
            'wpmcp/switch-theme',
            'free',
            'Activate (switch to) an installed theme. Snapshots the prior template/stylesheet options so it can be rolled back',
            [
                'type'       => 'object',
                'properties' => [
                    'stylesheet' => [ 'type' => 'string' ],
                    'session_id' => [ 'type' => 'string' ],
                ],
                'required'   => [ 'stylesheet' ],
            ],
            [$switch_theme, 'handle'],
            'switch_themes'
        ));
        $registrar->register(new Ability(
            'wpmcp/install-theme',
            'free',
            'Install a theme from wordpress.org by slug, optionally activating it. Additive only; nothing to roll back',
            [
                'type'       => 'object',
                'properties' => [
                    'slug'     => [ 'type' => 'string' ],
                    'activate' => [ 'type' => 'boolean' ],
                ],
                'required'   => [ 'slug' ],
            ],
            [$install_theme, 'handle'],
            'install_themes'
        ));
        $registrar->register(new Ability(
            'wpmcp/update-theme',
            'free',
            'Update an installed theme to the latest wordpress.org version. Disabled by default (wpmcp_enable_update_theme filter) and requires confirm:true. File changes are not rollback-able',
            [
                'type'       => 'object',
                'properties' => [
                    'stylesheet' => [ 'type' => 'string' ],
                    'confirm'    => [ 'type' => 'boolean' ],
                ],
                'required'   => [ 'stylesheet', 'confirm' ],
            ],
            [$update_theme, 'handle'],
            'update_themes'
        ));
        $registrar->register(new Ability(
            'wpmcp/delete-theme',
            'free',
            'Permanently delete an installed theme\'s files. Disabled by default (wpmcp_enable_delete_theme filter) and requires confirm:true. Refuses the active theme (or its active parent). Not rollback-able',
            [
                'type'       => 'object',
                'properties' => [
                    'stylesheet' => [ 'type' => 'string' ],
                    'confirm'    => [ 'type' => 'boolean' ],
                ],
                'required'   => [ 'stylesheet', 'confirm' ],
            ],
            [$delete_theme, 'handle'],
            'delete_themes'
        ));

        $list_tables    = new List_Tables();
        $describe_table = new Describe_Table();
        $query          = new Query();
        $insert_row     = new Insert_Row();
        $update_rows    = new Update_Rows();
        $delete_rows    = new Delete_Rows();

        $registrar->register(new Ability(
            'wpmcp/list-tables',
            'free',
            'List database tables with estimated row counts and sizes',
            [
                'type'       => 'object',
                'properties' => [],
            ],
            [$list_tables, 'handle'],
            'manage_options'
        ));
        $registrar->register(new Ability(
            'wpmcp/describe-table',
            'free',
            'Return the columns, types, and keys of a database table',
            [
                'type'       => 'object',
                'properties' => [
                    'table' => [ 'type' => 'string' ],
                ],
                'required'   => [ 'table' ],
            ],
            [$describe_table, 'handle'],
            'manage_options'
        ));
        $registrar->register(new Ability(
            'wpmcp/query',
            'free',
            'Run a read-only SQL query (SELECT/SHOW/DESCRIBE/EXPLAIN/WITH). Writes, DDL, stacked statements, and file-access SQL are rejected before execution. Results are capped',
            [
                'type'       => 'object',
                'properties' => [
                    'sql'   => [ 'type' => 'string' ],
                    'limit' => [ 'type' => 'integer' ],
                ],
                'required'   => [ 'sql' ],
            ],
            [$query, 'handle'],
            'manage_options'
        ));
        $registrar->register(new Ability(
            'wpmcp/insert-row',
            'free',
            'Insert a row into a table via $wpdb->insert() (parameterized). Refuses protected tables. Disabled by default (wpmcp_enable_db_writes filter)',
            [
                'type'       => 'object',
                'properties' => [
                    'table' => [ 'type' => 'string' ],
                    'data'  => [ 'type' => 'object' ],
                ],
                'required'   => [ 'table', 'data' ],
            ],
            [$insert_row, 'handle'],
            'manage_options'
        ));
        $registrar->register(new Ability(
            'wpmcp/update-rows',
            'free',
            'Update rows matching a mandatory equality WHERE via $wpdb->update() (parameterized). Refuses protected tables. Disabled by default (wpmcp_enable_db_writes filter). Captures a before-image to the write audit log and honestly reports recoverable:false (no generic-table rollback)',
            [
                'type'       => 'object',
                'properties' => [
                    'table' => [ 'type' => 'string' ],
                    'data'  => [ 'type' => 'object' ],
                    'where' => [ 'type' => 'object' ],
                ],
                'required'   => [ 'table', 'data', 'where' ],
            ],
            [$update_rows, 'handle'],
            'manage_options'
        ));
        $registrar->register(new Ability(
            'wpmcp/delete-rows',
            'free',
            'Delete rows matching a mandatory equality WHERE via $wpdb->delete() (parameterized). Requires confirm:true. Refuses protected tables. Disabled by default (wpmcp_enable_db_writes filter). Captures a before-image to the write audit log and honestly reports recoverable:false (no generic-table rollback)',
            [
                'type'       => 'object',
                'properties' => [
                    'table'   => [ 'type' => 'string' ],
                    'where'   => [ 'type' => 'object' ],
                    'confirm' => [ 'type' => 'boolean' ],
                ],
                'required'   => [ 'table', 'where' ],
            ],
            [$delete_rows, 'handle'],
            'manage_options'
        ));
    }
}
