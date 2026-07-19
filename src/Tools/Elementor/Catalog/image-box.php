<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'image-box' widget (issue #59). */
return [
    'type' => 'image-box',
    'title' => 'Image Box',
    'category' => 'general',
    'purpose' => 'Image with a heading and description, commonly used for feature grids.',
    'keywords' => [
        'feature',
        'card',
        'service',
        'box',
    ],
    'requires' => 'elementor',
    'params' => [
        'image' => [
            'type' => 'media',
            'description' => 'The box image ({url, id}).',
            'required' => true,
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
            'type' => 'choice',
            'description' => 'Image position relative to the content.',
            'enum' => [
                'left',
                'top',
                'right',
            ],
            'default' => 'top',
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
