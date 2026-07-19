<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'icon-list' widget (issue #59). */
return [
    'type' => 'icon-list',
    'title' => 'Icon List',
    'category' => 'general',
    'purpose' => 'Vertical or inline list of items, each with an icon and optional link.',
    'keywords' => [
        'list',
        'bullets',
        'features',
        'checklist',
    ],
    'requires' => 'elementor',
    'params' => [
        'icon_list' => [
            'type' => 'repeater',
            'description' => 'The list items.',
            'required' => true,
            'fields' => [
                'text' => [
                    'type' => 'string',
                    'description' => 'The item text.',
                    'required' => true,
                ],
                'selected_icon' => [
                    'type' => 'icons',
                    'description' => 'The item icon ({value, library}).',
                ],
                'link' => [
                    'type' => 'link',
                    'description' => 'Optional URL the item links to.',
                ],
            ],
        ],
        'view' => [
            'type' => 'choice',
            'description' => 'Traditional (vertical) or inline layout.',
            'enum' => [
                'traditional',
                'inline',
            ],
            'default' => 'traditional',
        ],
        'link_click' => [
            'type' => 'choice',
            'description' => 'Clickable area when an item has a link.',
            'enum' => [
                'full_width',
                'inline',
            ],
            'default' => 'full_width',
        ],
    ],
];
