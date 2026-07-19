<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'spacer' widget (issue #59). */
return [
    'type' => 'spacer',
    'title' => 'Spacer',
    'category' => 'basic',
    'purpose' => 'Vertical empty space between elements.',
    'keywords' => [
        'space',
        'gap',
        'margin',
        'whitespace',
    ],
    'requires' => 'elementor',
    'params' => [
        'space' => [
            'type' => 'slider',
            'description' => 'Height of the space ({size, unit} or a bare px number).',
            'default' => 50,
        ],
    ],
];
