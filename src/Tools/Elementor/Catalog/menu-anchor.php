<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'menu-anchor' widget (issue #59). */
return [
    'type' => 'menu-anchor',
    'title' => 'Menu Anchor',
    'category' => 'general',
    'purpose' => 'Invisible anchor target for same-page menu links.',
    'keywords' => [
        'anchor',
        'jump',
        'scroll',
        'target',
    ],
    'requires' => 'elementor',
    'params' => [
        'anchor' => [
            'type' => 'string',
            'description' => 'The anchor id (without the # prefix).',
            'required' => true,
        ],
    ],
];
