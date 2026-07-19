<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor Pro 'blockquote' widget (issue #59). */
return [
    'type' => 'blockquote',
    'title' => 'Blockquote',
    'category' => 'pro-elements',
    'purpose' => 'Styled quotation with author and optional tweet button.',
    'keywords' => [
        'quote',
        'citation',
        'tweet',
    ],
    'requires' => 'elementor-pro',
    'params' => [
        'blockquote_content' => [
            'type' => 'html',
            'description' => 'The quote text.',
            'required' => true,
        ],
        'author_name' => [
            'type' => 'string',
            'description' => 'The quote author.',
        ],
        'blockquote_skin' => [
            'type' => 'choice',
            'description' => 'Visual skin.',
            'enum' => [
                'border',
                'quotation',
                'boxed',
                'clean',
            ],
            'default' => 'border',
        ],
        'tweet_button' => [
            'type' => 'bool',
            'description' => 'Show a tweet button.',
            'default' => true,
        ],
        'tweet_button_label' => [
            'type' => 'string',
            'description' => 'The tweet button label.',
        ],
    ],
];
