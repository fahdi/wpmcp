<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor Pro 'slides' widget (issue #59). */
return [
    'type' => 'slides',
    'title' => 'Slides',
    'category' => 'pro-elements',
    'purpose' => 'Full-width slider with per-slide heading, text, and button.',
    'keywords' => [
        'slider',
        'hero',
        'carousel',
        'banner',
    ],
    'requires' => 'elementor-pro',
    'params' => [
        'slides' => [
            'type' => 'repeater',
            'description' => 'The slides.',
            'required' => true,
            'fields' => [
                'heading' => [
                    'type' => 'string',
                    'description' => 'The slide heading.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'The slide description.',
                ],
                'button_text' => [
                    'type' => 'string',
                    'description' => 'The slide button label.',
                ],
                'link' => [
                    'type' => 'link',
                    'description' => 'Where the slide button links to.',
                ],
            ],
        ],
    ],
];
