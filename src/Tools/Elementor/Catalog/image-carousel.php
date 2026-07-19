<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'image-carousel' widget (issue #59). */
return [
    'type' => 'image-carousel',
    'title' => 'Image Carousel',
    'category' => 'general',
    'purpose' => 'Sliding carousel of images with navigation and autoplay.',
    'keywords' => [
        'slider',
        'gallery',
        'slideshow',
        'swiper',
    ],
    'requires' => 'elementor',
    'params' => [
        'carousel' => [
            'type' => 'gallery',
            'description' => 'The carousel images (array of {url, id}).',
            'required' => true,
        ],
        'slides_to_show' => [
            'type' => 'choice',
            'description' => 'Images visible at once.',
            'enum' => [
                '1',
                '2',
                '3',
                '4',
                '5',
                '6',
                '7',
                '8',
                '9',
                '10',
            ],
            'responsive' => true,
        ],
        'navigation' => [
            'type' => 'choice',
            'description' => 'Navigation UI.',
            'enum' => [
                'both',
                'arrows',
                'dots',
                'none',
            ],
            'default' => 'both',
        ],
        'autoplay' => [
            'type' => 'bool',
            'description' => 'Advance slides automatically.',
            'default' => true,
        ],
        'autoplay_speed' => [
            'type' => 'integer',
            'description' => 'Autoplay interval in milliseconds.',
            'default' => 5000,
        ],
        'infinite' => [
            'type' => 'bool',
            'description' => 'Loop the carousel.',
            'default' => true,
        ],
        'pause_on_hover' => [
            'type' => 'bool',
            'description' => 'Pause autoplay on hover.',
            'default' => true,
        ],
        'link_to' => [
            'type' => 'choice',
            'description' => 'What each image links to.',
            'enum' => [
                'none',
                'file',
                'custom',
            ],
            'default' => 'none',
        ],
        'caption_type' => [
            'type' => 'string',
            'description' => 'Caption to show under each image (title, caption, or description; empty for none).',
        ],
    ],
];
