<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'social-icons' widget (issue #59). */
return [
    'type' => 'social-icons',
    'title' => 'Social Icons',
    'category' => 'general',
    'purpose' => 'Row of social network icons linking to profiles.',
    'keywords' => [
        'facebook',
        'twitter',
        'instagram',
        'linkedin',
        'social',
    ],
    'requires' => 'elementor',
    'params' => [
        'social_icon_list' => [
            'type' => 'repeater',
            'description' => 'The social icons.',
            'required' => true,
            'fields' => [
                'social_icon' => [
                    'type' => 'icons',
                    'description' => 'The network icon ({value, library}), e.g. fab fa-facebook.',
                    'required' => true,
                ],
                'link' => [
                    'type' => 'link',
                    'description' => 'The profile URL.',
                ],
            ],
        ],
        'shape' => [
            'type' => 'choice',
            'description' => 'Icon background shape.',
            'enum' => [
                'rounded',
                'square',
                'circle',
            ],
            'default' => 'rounded',
        ],
        'align' => [
            'type' => 'choice',
            'description' => 'Row alignment.',
            'enum' => [
                'left',
                'center',
                'right',
            ],
            'default' => 'center',
            'responsive' => true,
        ],
    ],
];
