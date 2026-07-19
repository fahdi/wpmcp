<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor Pro 'price-table' widget (issue #59). */
return [
    'type' => 'price-table',
    'title' => 'Price Table',
    'category' => 'pro-elements',
    'purpose' => 'Pricing plan card with features list and call-to-action.',
    'keywords' => [
        'pricing',
        'plan',
        'subscription',
        'tier',
    ],
    'requires' => 'elementor-pro',
    'params' => [
        'heading' => [
            'type' => 'string',
            'description' => 'The plan name.',
        ],
        'sub_heading' => [
            'type' => 'string',
            'description' => 'Short plan description.',
        ],
        'price' => [
            'type' => 'string',
            'description' => 'The price figure.',
        ],
        'period' => [
            'type' => 'string',
            'description' => 'Billing period label (e.g. Monthly).',
        ],
        'features_list' => [
            'type' => 'repeater',
            'description' => 'The plan features.',
            'fields' => [
                'item_text' => [
                    'type' => 'string',
                    'description' => 'The feature text.',
                    'required' => true,
                ],
                'selected_item_icon' => [
                    'type' => 'icons',
                    'description' => 'The feature icon ({value, library}).',
                ],
            ],
        ],
        'button_text' => [
            'type' => 'string',
            'description' => 'The call-to-action label.',
        ],
        'link' => [
            'type' => 'link',
            'description' => 'Where the call-to-action links to.',
        ],
    ],
];
