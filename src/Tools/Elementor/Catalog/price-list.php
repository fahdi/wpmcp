<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor Pro 'price-list' widget (issue #59). */
return [
    'type' => 'price-list',
    'title' => 'Price List',
    'category' => 'pro-elements',
    'purpose' => 'Menu-style list of items with prices and descriptions.',
    'keywords' => [
        'menu',
        'pricing',
        'restaurant',
        'services',
    ],
    'requires' => 'elementor-pro',
    'params' => [
        'price_list' => [
            'type' => 'repeater',
            'description' => 'The priced items.',
            'required' => true,
            'fields' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'The item name.',
                    'required' => true,
                ],
                'price' => [
                    'type' => 'string',
                    'description' => 'The item price.',
                ],
                'item_description' => [
                    'type' => 'string',
                    'description' => 'The item description.',
                ],
                'image' => [
                    'type' => 'media',
                    'description' => 'Optional item image ({url, id}).',
                ],
                'link' => [
                    'type' => 'link',
                    'description' => 'Optional URL the item links to.',
                ],
            ],
        ],
    ],
];
