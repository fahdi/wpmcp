<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'star-rating' widget (issue #59). */
return [
    'type' => 'star-rating',
    'title' => 'Star Rating',
    'category' => 'general',
    'purpose' => 'Static star rating display with an optional title.',
    'keywords' => [
        'stars',
        'review',
        'score',
    ],
    'requires' => 'elementor',
    'params' => [
        'rating' => [
            'type' => 'number',
            'description' => 'The rating value.',
            'required' => true,
            'default' => 5,
        ],
        'rating_scale' => [
            'type' => 'choice',
            'description' => 'Rating scale: out of 5 or out of 10.',
            'enum' => [
                '5',
                '10',
            ],
            'default' => '5',
        ],
        'star_style' => [
            'type' => 'choice',
            'description' => 'Star glyph style.',
            'enum' => [
                'star_fontawesome',
                'star_unicode',
            ],
            'default' => 'star_fontawesome',
        ],
        'unmarked_star_style' => [
            'type' => 'choice',
            'description' => 'Style of the unmarked stars.',
            'enum' => [
                'solid',
                'outline',
            ],
            'default' => 'solid',
        ],
        'title' => [
            'type' => 'string',
            'description' => 'Optional title shown next to the stars.',
        ],
        'align' => [
            'type' => 'choice',
            'description' => 'Alignment of the rating block.',
            'enum' => [
                'left',
                'center',
                'right',
                'justify',
            ],
            'responsive' => true,
        ],
    ],
];
