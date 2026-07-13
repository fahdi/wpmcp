<?php

namespace WPMCP\Identity;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Tracks which named identity (if any) is active for the current request.
 * Mirrors WPMCP\Pro\Gate's test-seam pattern exactly: a non-null in-memory
 * override takes precedence over production resolution, which falls back
 * to the wpmcp_current_identity filter. Passing null clears the override
 * (no identity is active unless something, e.g. a transport/auth layer,
 * sets it via that filter).
 */
class Identity_Context
{
    private static ?string $test_override = null;

    public static function set_current_for_tests(?string $name): void
    {
        self::$test_override = $name;
    }

    public static function current(): ?string
    {
        if (null !== self::$test_override) {
            return self::$test_override;
        }
        $identity = apply_filters('wpmcp_current_identity', null);
        return null !== $identity ? (string) $identity : null;
    }
}
