<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'counter' widget (issue #59). */
return [
    'type' => 'counter',
    'title' => 'Counter',
    'category' => 'general',
    'purpose' => 'Animated number counter with a title, prefix, and suffix.',
    'keywords' => [
        'number',
        'stats',
        'metric',
        'count',
    ],
    'requires' => 'elementor',
    'params' => [
        'ending_number' => [
            'type' => 'number',
            'description' => 'The number to count up to.',
            'required' => true,
            'default' => 100,
        ],
        'starting_number' => [
            'type' => 'number',
            'description' => 'The number to count from.',
            'default' => 0,
        ],
        'prefix' => [
            'type' => 'string',
            'description' => 'Text before the number.',
        ],
        'suffix' => [
            'type' => 'string',
            'description' => 'Text after the number.',
        ],
        'duration' => [
            'type' => 'integer',
            'description' => 'Animation duration in milliseconds.',
            'default' => 2000,
        ],
        'thousand_separator' => [
            'type' => 'bool',
            'description' => 'Show a thousands separator.',
            'default' => true,
        ],
        'title' => [
            'type' => 'string',
            'description' => 'Title shown under the number.',
            'default' => 'Cool Number',
        ],
    ],
];
