<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor Pro 'call-to-action' widget (issue #59). */
return [
    'type' => 'call-to-action',
    'title' => 'Call to Action',
    'category' => 'pro-elements',
    'purpose' => 'Image or background banner with title, description, and button.',
    'keywords' => [
        'cta',
        'banner',
        'promo',
        'conversion',
    ],
    'requires' => 'elementor-pro',
    'params' => [
        'title' => [
            'type' => 'string',
            'description' => 'The banner title.',
            'required' => true,
        ],
        'description' => [
            'type' => 'string',
            'description' => 'The banner description.',
        ],
        'button' => [
            'type' => 'string',
            'description' => 'The button label.',
        ],
        'link' => [
            'type' => 'link',
            'description' => 'Where the button links to.',
        ],
        'bg_image' => [
            'type' => 'media',
            'description' => 'Background image ({url, id}).',
        ],
    ],
];
