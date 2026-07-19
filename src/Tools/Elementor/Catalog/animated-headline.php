<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor Pro 'animated-headline' widget (issue #59). */
return [
    'type' => 'animated-headline',
    'title' => 'Animated Headline',
    'category' => 'pro-elements',
    'purpose' => 'Headline with a rotating or highlighted animated segment.',
    'keywords' => [
        'heading',
        'rotate',
        'highlight',
        'typing',
    ],
    'requires' => 'elementor-pro',
    'params' => [
        'headline_style' => [
            'type' => 'choice',
            'description' => 'Highlight a fixed word or rotate through words.',
            'enum' => [
                'highlight',
                'rotate',
            ],
            'default' => 'rotate',
        ],
        'animation_type' => [
            'type' => 'string',
            'description' => 'Rotation animation (e.g. typing, clip, flip, swirl, blinds, drop-in, wave, slide, slide-down).',
        ],
        'before_text' => [
            'type' => 'string',
            'description' => 'Static text before the animated segment.',
        ],
        'highlighted_text' => [
            'type' => 'string',
            'description' => 'The highlighted word (highlight style).',
        ],
        'rotating_text' => [
            'type' => 'string',
            'description' => 'Rotating words, one per line (rotate style).',
        ],
        'after_text' => [
            'type' => 'string',
            'description' => 'Static text after the animated segment.',
        ],
    ],
];
