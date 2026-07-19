<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'image' widget (issue #59). */
return [
    'type' => 'image',
    'title' => 'Image',
    'category' => 'basic',
    'purpose' => 'Place a single image, optionally captioned and linked.',
    'keywords' => [
        'picture',
        'photo',
        'media',
        'visual',
    ],
    'requires' => 'elementor',
    'params' => [
        'image' => [
            'type' => 'media',
            'description' => 'The image to display ({url, id}).',
            'required' => true,
        ],
        'image_size' => [
            'type' => 'string',
            'description' => 'Registered WordPress image size to render (e.g. thumbnail, medium, large, full).',
            'default' => 'large',
        ],
        'caption_source' => [
            'type' => 'choice',
            'description' => 'Where the caption comes from.',
            'enum' => [
                'none',
                'attachment',
                'custom',
            ],
            'default' => 'none',
        ],
        'caption' => [
            'type' => 'string',
            'description' => 'Custom caption text (used when caption_source is custom).',
        ],
        'link_to' => [
            'type' => 'choice',
            'description' => 'What the image links to.',
            'enum' => [
                'none',
                'file',
                'custom',
            ],
            'default' => 'none',
        ],
        'link' => [
            'type' => 'link',
            'description' => 'Custom URL (used when link_to is custom).',
        ],
    ],
];
