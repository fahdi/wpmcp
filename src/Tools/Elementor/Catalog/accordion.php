<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'accordion' widget (issue #59). */
return [
    'type' => 'accordion',
    'title' => 'Accordion',
    'category' => 'general',
    'purpose' => 'Collapsible content sections; one open at a time (classic widget).',
    'keywords' => [
        'collapse',
        'faq',
        'expand',
        'sections',
    ],
    'requires' => 'elementor',
    'params' => [
        'tabs' => [
            'type' => 'repeater',
            'description' => 'The accordion sections.',
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
            'description' => 'Icon for the open section ({value, library}).',
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
        'faq_schema' => [
            'type' => 'bool',
            'description' => 'Emit FAQ structured data.',
            'default' => false,
        ],
    ],
];
