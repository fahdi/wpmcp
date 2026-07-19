<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'icon' widget (issue #59). */
return [
    'type' => 'icon',
    'title' => 'Icon',
    'category' => 'basic',
    'purpose' => 'Single icon, optionally framed or stacked and linked.',
    'keywords' => [
        'symbol',
        'fontawesome',
        'glyph',
    ],
    'requires' => 'elementor',
    'params' => [
        'selected_icon' => [
            'type' => 'icons',
            'description' => 'The icon to display ({value, library}).',
            'required' => true,
        ],
        'view' => [
            'type' => 'choice',
            'description' => 'Plain, stacked (solid background), or framed.',
            'enum' => [
                'default',
                'stacked',
                'framed',
            ],
            'default' => 'default',
        ],
        'shape' => [
            'type' => 'choice',
            'description' => 'Background/frame shape for stacked and framed views.',
            'enum' => [
                'circle',
                'square',
            ],
            'default' => 'circle',
        ],
        'link' => [
            'type' => 'link',
            'description' => 'Optional URL the icon links to.',
        ],
    ],
];
