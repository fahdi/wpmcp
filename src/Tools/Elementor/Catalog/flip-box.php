<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor Pro 'flip-box' widget (issue #59). */
return [
    'type' => 'flip-box',
    'title' => 'Flip Box',
    'category' => 'pro-elements',
    'purpose' => 'Two-sided card that flips on hover to reveal a back side.',
    'keywords' => [
        'card',
        'hover',
        'flip',
        'interactive',
    ],
    'requires' => 'elementor-pro',
    'params' => [
        'title_text_a' => [
            'type' => 'string',
            'description' => 'Front-side title.',
        ],
        'description_text_a' => [
            'type' => 'string',
            'description' => 'Front-side description.',
        ],
        'title_text_b' => [
            'type' => 'string',
            'description' => 'Back-side title.',
        ],
        'description_text_b' => [
            'type' => 'string',
            'description' => 'Back-side description.',
        ],
        'button_text' => [
            'type' => 'string',
            'description' => 'Back-side button label.',
        ],
        'link' => [
            'type' => 'link',
            'description' => 'Where the back-side button links to.',
        ],
    ],
];
