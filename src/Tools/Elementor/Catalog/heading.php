<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'heading' widget (issue #59). */
return [
    'type' => 'heading',
    'title' => 'Heading',
    'category' => 'basic',
    'purpose' => 'Add a title or section heading with a configurable HTML tag.',
    'keywords' => [
        'title',
        'text',
        'headline',
        'h1',
        'h2',
    ],
    'requires' => 'elementor',
    'params' => [
        'title' => [
            'type' => 'string',
            'description' => 'The heading text.',
            'required' => true,
            'default' => 'Add Your Heading Text Here',
        ],
        'header_size' => [
            'type' => 'choice',
            'description' => 'HTML tag rendered for the heading.',
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
            'default' => 'h2',
        ],
        'size' => [
            'type' => 'choice',
            'description' => 'Preset visual size of the heading.',
            'enum' => [
                'default',
                'small',
                'medium',
                'large',
                'xl',
                'xxl',
            ],
            'default' => 'default',
        ],
        'link' => [
            'type' => 'link',
            'description' => 'Optional URL the heading links to.',
        ],
    ],
];
