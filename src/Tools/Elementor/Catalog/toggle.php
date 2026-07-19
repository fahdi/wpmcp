<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'toggle' widget (issue #59). */
return [
    'type' => 'toggle',
    'title' => 'Toggle',
    'category' => 'general',
    'purpose' => 'Collapsible content sections; any number open at once (classic widget).',
    'keywords' => [
        'collapse',
        'faq',
        'expand',
        'accordion',
    ],
    'requires' => 'elementor',
    'params' => [
        'tabs' => [
            'type' => 'repeater',
            'description' => 'The toggle sections.',
            'required' => true,
            'fields' => [
                'tab_title' => [
                    'type' => 'string',
                    'description' => 'The section title.',
                    'required' => true,
                ],
                'tab_content' => [
                    'type' => 'html',
                    'description' => 'The section content.',
                    'required' => true,
                ],
            ],
        ],
        'selected_icon' => [
            'type' => 'icons',
            'description' => 'Icon for closed sections ({value, library}).',
        ],
        'selected_active_icon' => [
            'type' => 'icons',
            'description' => 'Icon for open sections ({value, library}).',
        ],
        'title_html_tag' => [
            'type' => 'choice',
            'description' => 'HTML tag for section titles.',
            'enum' => [
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'h6',
                'div',
            ],
            'default' => 'div',
        ],
    ],
];
