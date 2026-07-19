<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'image-gallery' widget (issue #59). */
return [
    'type' => 'image-gallery',
    'title' => 'Image Gallery',
    'category' => 'general',
    'purpose' => 'Static WordPress image gallery grid.',
    'keywords' => [
        'photos',
        'grid',
        'portfolio',
        'thumbnails',
    ],
    'requires' => 'elementor',
    'params' => [
        'wp_gallery' => [
            'type' => 'gallery',
            'description' => 'The gallery images (array of {url, id}).',
            'required' => true,
        ],
        'gallery_columns' => [
            'type' => 'integer',
            'description' => 'Number of columns (1-10).',
            'default' => 4,
        ],
        'gallery_link' => [
            'type' => 'choice',
            'description' => 'What each image links to.',
            'enum' => [
                'file',
                'attachment',
                'none',
            ],
            'default' => 'file',
        ],
        'gallery_rand' => [
            'type' => 'string',
            'description' => 'Ordering: empty for gallery order, rand for random.',
        ],
    ],
];
