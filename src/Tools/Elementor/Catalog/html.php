<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'html' widget (issue #59). */
return [
    'type' => 'html',
    'title' => 'HTML',
    'category' => 'general',
    'purpose' => 'Raw custom HTML block.',
    'keywords' => [
        'code',
        'embed',
        'custom',
        'markup',
    ],
    'requires' => 'elementor',
    'params' => [
        'html' => [
            'type' => 'html',
            'description' => 'The raw HTML to render.',
            'required' => true,
        ],
    ],
];
