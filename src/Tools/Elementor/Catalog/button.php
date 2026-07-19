<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'button' widget (issue #59). */
return [
    'type' => 'button',
    'title' => 'Button',
    'category' => 'basic',
    'purpose' => 'Call-to-action button with a link, size, and optional icon.',
    'keywords' => [
        'cta',
        'click',
        'link',
        'action',
    ],
    'requires' => 'elementor',
    'params' => [
        'text' => [
            'type' => 'string',
            'description' => 'The button label.',
            'required' => true,
            'default' => 'Click here',
        ],
        'link' => [
            'type' => 'link',
            'description' => 'Where the button links to.',
        ],
        'align' => [
            'type' => 'choice',
            'description' => 'Button alignment within its column.',
            'enum' => [
                'left',
                'center',
                'right',
                'justify',
            ],
            'responsive' => true,
        ],
        'size' => [
            'type' => 'choice',
            'description' => 'Preset button size.',
            'enum' => [
                'xs',
                'sm',
                'md',
                'lg',
                'xl',
            ],
            'default' => 'sm',
        ],
        'selected_icon' => [
            'type' => 'icons',
            'description' => 'Optional icon shown in the button ({value, library}).',
        ],
        'button_css_id' => [
            'type' => 'string',
            'description' => 'CSS id applied to the button anchor (no hash).',
        ],
    ],
];
