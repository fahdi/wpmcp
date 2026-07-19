<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'icon-box' widget (issue #59). */
return [
    'type' => 'icon-box',
    'title' => 'Icon Box',
    'category' => 'general',
    'purpose' => 'Icon with a heading and description, commonly used for feature grids.',
    'keywords' => [
        'feature',
        'card',
        'service',
        'box',
    ],
    'requires' => 'elementor',
    'params' => [
        'selected_icon' => [
            'type' => 'icons',
            'description' => 'The box icon ({value, library}).',
        ],
        'view' => [
            'type' => 'choice',
            'description' => 'Plain, stacked, or framed icon.',
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
        'title_text' => [
            'type' => 'string',
            'description' => 'The box heading.',
            'default' => 'This is the heading',
        ],
        'description_text' => [
            'type' => 'string',
            'description' => 'The box description.',
        ],
        'link' => [
            'type' => 'link',
            'description' => 'Optional URL the whole box links to.',
        ],
        'position' => [
            'type' => 'string',
            'description' => 'Icon position relative to the content (logical values, e.g. block-start / inline-start).',
            'responsive' => true,
        ],
        'title_size' => [
            'type' => 'choice',
            'description' => 'HTML tag for the box heading.',
            'enum' => [
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'h6',
                'div',
                'span',
                'p',
            ],
            'default' => 'h3',
        ],
    ],
];
