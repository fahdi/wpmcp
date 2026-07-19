<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'divider' widget (issue #59). */
return [
    'type' => 'divider',
    'title' => 'Divider',
    'category' => 'basic',
    'purpose' => 'Horizontal separator line, optionally with text or an icon.',
    'keywords' => [
        'separator',
        'hr',
        'line',
        'rule',
    ],
    'requires' => 'elementor',
    'params' => [
        'look' => [
            'type' => 'choice',
            'description' => 'Line only, line with icon, or line with text.',
            'enum' => [
                'line',
                'line_icon',
                'line_text',
            ],
            'default' => 'line',
        ],
        'style' => [
            'type' => 'string',
            'description' => 'Line style (e.g. solid, double, dotted, dashed, or a fancy pattern).',
            'default' => 'solid',
        ],
        'text' => [
            'type' => 'string',
            'description' => 'Text shown on the divider (used when look is line_text).',
        ],
        'weight' => [
            'type' => 'slider',
            'description' => 'Line thickness in px.',
            'default' => 1,
        ],
    ],
];
