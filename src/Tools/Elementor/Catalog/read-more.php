<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'read-more' widget (issue #59). */
return [
    'type' => 'read-more',
    'title' => 'Read More',
    'category' => 'general',
    'purpose' => 'The WordPress "more" cut point for archive excerpts.',
    'keywords' => [
        'more',
        'excerpt',
        'continue',
    ],
    'requires' => 'elementor',
    'params' => [
        'link_text' => [
            'type' => 'string',
            'description' => 'The read-more link text.',
            'default' => 'Continue reading',
        ],
    ],
];
