<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor Pro 'share-buttons' widget (issue #59). */
return [
    'type' => 'share-buttons',
    'title' => 'Share Buttons',
    'category' => 'pro-elements',
    'purpose' => 'Social sharing buttons for the current page.',
    'keywords' => [
        'share',
        'social',
        'facebook',
        'twitter',
    ],
    'requires' => 'elementor-pro',
    'params' => [
        'share_buttons' => [
            'type' => 'repeater',
            'description' => 'The networks to offer.',
            'required' => true,
            'fields' => [
                'button' => [
                    'type' => 'string',
                    'description' => 'The network key (e.g. facebook, twitter, linkedin, whatsapp).',
                    'required' => true,
                ],
            ],
        ],
    ],
];
