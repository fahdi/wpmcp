<?php

namespace WPMCP\MCP;

if (! defined('ABSPATH')) {
    exit;
}

class Ability
{
    /**
     * $capability is the WordPress capability a caller must hold for this
     * ability's permission_callback to allow execution. It defaults to
     * 'edit_posts' so every existing ability keeps its original gate; only
     * sensitive tools (e.g. user management) pass a stronger capability.
     */
    public function __construct(
        public string $name,
        public string $tier,
        public string $description,
        public array $input_schema,
        public $handler,
        public string $capability = 'edit_posts'
    ) {
    }
}
