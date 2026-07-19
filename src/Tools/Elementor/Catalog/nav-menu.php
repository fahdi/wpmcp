<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor Pro 'nav-menu' widget (issue #59). */
return [
    'type' => 'nav-menu',
    'title' => 'Nav Menu',
    'category' => 'pro-elements',
    'purpose' => 'Site navigation rendered from a WordPress menu.',
    'keywords' => [
        'menu',
        'navigation',
        'header',
        'links',
    ],
    'requires' => 'elementor-pro',
    'params' => [
        'menu' => [
            'type' => 'string',
            'description' => 'The WordPress menu slug or id to render.',
        ],
        'layout' => [
            'type' => 'choice',
            'description' => 'Menu layout.',
            'enum' => [
                'horizontal',
                'vertical',
                'dropdown',
            ],
            'default' => 'horizontal',
        ],
        'pointer' => [
            'type' => 'string',
            'description' => 'Hover pointer style (e.g. underline, overline, framed, background, text, none).',
        ],
    ],
];
