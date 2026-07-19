<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'google_maps' widget (issue #59). */
return [
    'type' => 'google_maps',
    'title' => 'Google Maps',
    'category' => 'basic',
    'purpose' => 'Embedded Google Map centered on an address.',
    'keywords' => [
        'map',
        'location',
        'address',
        'directions',
    ],
    'requires' => 'elementor',
    'params' => [
        'address' => [
            'type' => 'string',
            'description' => 'The address or place to center the map on.',
            'required' => true,
        ],
        'zoom' => [
            'type' => 'slider',
            'description' => 'Zoom level (1-20).',
            'default' => 10,
        ],
    ],
];
