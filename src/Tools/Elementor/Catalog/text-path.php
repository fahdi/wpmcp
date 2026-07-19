<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'text-path' widget (issue #59). */
return [
    'type' => 'text-path',
    'title' => 'Text Path',
    'category' => 'general',
    'purpose' => 'Text following an SVG path shape.',
    'keywords' => [
        'svg',
        'curve',
        'wave',
        'circular-text',
    ],
    'requires' => 'elementor',
    'params' => [
        'text' => [
            'type' => 'string',
            'description' => 'The text to draw along the path.',
            'required' => true,
            'default' => 'Add Your Curvy Text Here',
        ],
        'path' => [
            'type' => 'string',
            'description' => 'The path shape (e.g. wave, arc, circle, line, oval, spiral).',
            'default' => 'wave',
        ],
        'link' => [
            'type' => 'link',
            'description' => 'Optional URL the text links to.',
        ],
        'align' => [
            'type' => 'choice',
            'description' => 'Alignment of the path block.',
            'enum' => [
                'left',
                'center',
                'right',
            ],
            'responsive' => true,
        ],
    ],
];
