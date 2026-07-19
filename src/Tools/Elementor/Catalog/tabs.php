<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'tabs' widget (issue #59). */
return [
    'type' => 'tabs',
    'title' => 'Tabs',
    'category' => 'general',
    'purpose' => 'Tabbed content panels (classic widget).',
    'keywords' => [
        'toggle',
        'panels',
        'sections',
    ],
    'requires' => 'elementor',
    'params' => [
        'tabs' => [
            'type' => 'repeater',
            'description' => 'The tab panels.',
            'required' => true,
            'fields' => [
                'tab_title' => [
                    'type' => 'string',
                    'description' => 'The tab label.',
                    'required' => true,
                ],
                'tab_content' => [
                    'type' => 'html',
                    'description' => 'The tab panel content.',
                    'required' => true,
                ],
            ],
        ],
        'type' => [
            'type' => 'choice',
            'description' => 'Tab bar orientation.',
            'enum' => [
                'horizontal',
                'vertical',
            ],
            'default' => 'horizontal',
        ],
    ],
];
