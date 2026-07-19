<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'rating' widget (issue #59). */
return [
    'type' => 'rating',
    'title' => 'Rating',
    'category' => 'general',
    'purpose' => 'Icon-based rating display (the modern replacement for Star Rating).',
    'keywords' => [
        'stars',
        'review',
        'score',
    ],
    'requires' => 'elementor',
    'params' => [
        'rating_value' => [
            'type' => 'number',
            'description' => 'The rating value.',
            'default' => 5,
        ],
        'rating_scale' => [
            'type' => 'slider',
            'description' => 'Maximum rating (number of icons).',
            'default' => 5,
        ],
        'rating_icon' => [
            'type' => 'icons',
            'description' => 'The icon used for each rating unit ({value, library}).',
        ],
    ],
];
