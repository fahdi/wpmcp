<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols -- ABSPATH guard is an intentional side effect.

namespace WPMCP;

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
        // Additional services wired in later tasks (MCP\Registrar, etc.)
    }
}
