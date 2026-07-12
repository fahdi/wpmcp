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
    }
}
