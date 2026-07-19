<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'sidebar' widget (issue #59). */
return [
    'type' => 'sidebar',
    'title' => 'Sidebar',
    'category' => 'general',
    'purpose' => 'Render a registered WordPress sidebar (widget area).',
    'keywords' => [
        'widgets',
        'widget-area',
    ],
    'requires' => 'elementor',
    'params' => [
        'sidebar' => [
            'type' => 'string',
            'description' => 'The registered sidebar id to render.',
            'required' => true,
        ],
    ],
];
