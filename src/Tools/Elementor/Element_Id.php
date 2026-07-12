<?php

namespace WPMCP\Tools\Elementor;

if (! defined('ABSPATH')) {
    exit;
}

/** Generates element ids in Elementor's own 7-character hex format. */
class Element_Id
{
    public static function generate(): string
    {
        return substr(bin2hex(random_bytes(4)), 0, 7);
    }
}
