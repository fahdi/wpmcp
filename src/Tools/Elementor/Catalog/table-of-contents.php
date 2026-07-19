<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor Pro 'table-of-contents' widget (issue #59). */
return [
    'type' => 'table-of-contents',
    'title' => 'Table of Contents',
    'category' => 'pro-elements',
    'purpose' => 'Auto-generated table of contents from the page headings.',
    'keywords' => [
        'toc',
        'index',
        'navigation',
        'headings',
    ],
    'requires' => 'elementor-pro',
    'params' => [
        'title' => [
            'type' => 'string',
            'description' => 'The box title.',
            'default' => 'Table of Contents',
        ],
        'marker_view' => [
            'type' => 'string',
            'description' => 'List markers: numbers, bullets, or none.',
            'default' => 'numbers',
        ],
    ],
];
