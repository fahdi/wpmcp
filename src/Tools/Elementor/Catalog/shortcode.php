<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'shortcode' widget (issue #59). */
return [
    'type' => 'shortcode',
    'title' => 'Shortcode',
    'category' => 'general',
    'purpose' => 'Render any WordPress shortcode.',
    'keywords' => [
        'embed',
        'plugin',
        'snippet',
    ],
    'requires' => 'elementor',
    'params' => [
        'shortcode' => [
            'type' => 'string',
            'description' => 'The shortcode to render, e.g. [gallery ids="1,2"].',
            'required' => true,
        ],
    ],
];
