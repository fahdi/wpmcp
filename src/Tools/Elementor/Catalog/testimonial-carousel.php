<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor Pro 'testimonial-carousel' widget (issue #59). */
return [
    'type' => 'testimonial-carousel',
    'title' => 'Testimonial Carousel',
    'category' => 'pro-elements',
    'purpose' => 'Rotating carousel of customer testimonials.',
    'keywords' => [
        'reviews',
        'quotes',
        'social-proof',
        'slider',
    ],
    'requires' => 'elementor-pro',
    'params' => [
        'slides' => [
            'type' => 'repeater',
            'description' => 'The testimonials.',
            'required' => true,
            'fields' => [
                'content' => [
                    'type' => 'html',
                    'description' => 'The testimonial quote.',
                    'required' => true,
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'The person quoted.',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Their role or company.',
                ],
                'image' => [
                    'type' => 'media',
                    'description' => 'Their photo ({url, id}).',
                ],
            ],
        ],
    ],
];
