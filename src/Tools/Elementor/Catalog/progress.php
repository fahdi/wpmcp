<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'progress' widget (issue #59). */
return [
    'type' => 'progress',
    'title' => 'Progress Bar',
    'category' => 'general',
    'purpose' => 'Horizontal progress bar with a title and percentage.',
    'keywords' => [
        'bar',
        'skill',
        'percentage',
        'meter',
    ],
    'requires' => 'elementor',
    'params' => [
        'percent' => [
            'type' => 'slider',
            'description' => 'Progress percentage (0-100).',
            'required' => true,
            'default' => 50,
            'unit' => '%',
        ],
        'title' => [
            'type' => 'string',
            'description' => 'Title shown above the bar.',
            'default' => 'My Skill',
        ],
        'progress_type' => [
            'type' => 'string',
            'description' => 'Semantic type (info, success, warning, danger; empty for default).',
        ],
        'display_percentage' => [
            'type' => 'bool',
            'description' => 'Show the percentage on the bar.',
            'default' => true,
            'on' => 'show',
            'off' => 'hide',
        ],
        'inner_text' => [
            'type' => 'string',
            'description' => 'Text shown inside the bar.',
            'default' => 'Web Designer',
        ],
    ],
];
