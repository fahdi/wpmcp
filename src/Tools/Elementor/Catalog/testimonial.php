<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'testimonial' widget (issue #59). */
return [
    'type' => 'testimonial',
    'title' => 'Testimonial',
    'category' => 'general',
    'purpose' => 'Customer quote with name, role, and photo.',
    'keywords' => [
        'quote',
        'review',
        'social-proof',
        'customer',
    ],
    'requires' => 'elementor',
    'params' => [
        'testimonial_content' => [
            'type' => 'html',
            'description' => 'The testimonial quote.',
            'required' => true,
        ],
        'testimonial_name' => [
            'type' => 'string',
            'description' => 'The person quoted.',
            'default' => 'John Doe',
        ],
        'testimonial_job' => [
            'type' => 'string',
            'description' => 'Their role or company.',
            'default' => 'Designer',
        ],
        'testimonial_image' => [
            'type' => 'media',
            'description' => 'Their photo ({url, id}).',
        ],
        'link' => [
            'type' => 'link',
            'description' => 'Optional URL for the name.',
        ],
        'testimonial_image_position' => [
            'type' => 'choice',
            'description' => 'Photo placement.',
            'enum' => [
                'aside',
                'top',
            ],
            'default' => 'aside',
        ],
    ],
];
