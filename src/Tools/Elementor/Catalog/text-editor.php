<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'text-editor' widget (issue #59). */
return [
    'type' => 'text-editor',
    'title' => 'Text Editor',
    'category' => 'basic',
    'purpose' => 'Rich text block for paragraphs and general copy.',
    'keywords' => [
        'paragraph',
        'copy',
        'wysiwyg',
        'content',
    ],
    'requires' => 'elementor',
    'params' => [
        'editor' => [
            'type' => 'html',
            'description' => 'The rich-text HTML content.',
            'required' => true,
        ],
        'drop_cap' => [
            'type' => 'bool',
            'description' => 'Enlarge the first letter as a drop cap.',
            'default' => false,
        ],
    ],
];
