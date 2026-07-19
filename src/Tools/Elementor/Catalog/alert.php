<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'alert' widget (issue #59). */
return [
    'type' => 'alert',
    'title' => 'Alert',
    'category' => 'general',
    'purpose' => 'Colored notice box with a title, description, and dismiss button.',
    'keywords' => [
        'notice',
        'message',
        'warning',
        'banner',
    ],
    'requires' => 'elementor',
    'params' => [
        'alert_title' => [
            'type' => 'string',
            'description' => 'The alert title.',
            'required' => true,
            'default' => 'This is an Alert',
        ],
        'alert_description' => [
            'type' => 'string',
            'description' => 'The alert body text.',
        ],
        'alert_type' => [
            'type' => 'choice',
            'description' => 'Semantic color of the alert.',
            'enum' => [
                'info',
                'success',
                'warning',
                'danger',
            ],
            'default' => 'info',
        ],
        'show_dismiss' => [
            'type' => 'bool',
            'description' => 'Show a dismiss button.',
            'default' => true,
            'on' => 'show',
            'off' => '',
        ],
        'dismiss_icon' => [
            'type' => 'icons',
            'description' => 'Custom dismiss icon ({value, library}).',
        ],
    ],
];
