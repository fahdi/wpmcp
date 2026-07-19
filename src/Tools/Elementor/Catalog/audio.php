<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'audio' widget (issue #59). */
return [
    'type' => 'audio',
    'title' => 'SoundCloud',
    'category' => 'general',
    'purpose' => 'Embedded SoundCloud audio player.',
    'keywords' => [
        'soundcloud',
        'music',
        'podcast',
        'player',
    ],
    'requires' => 'elementor',
    'params' => [
        'link' => [
            'type' => 'link',
            'description' => 'The SoundCloud track or playlist URL.',
            'required' => true,
        ],
        'sc_auto_play' => [
            'type' => 'bool',
            'description' => 'Start playing automatically.',
            'default' => false,
        ],
        'sc_show_user' => [
            'type' => 'bool',
            'description' => 'Show the uploader.',
            'default' => true,
        ],
        'sc_show_artwork' => [
            'type' => 'bool',
            'description' => 'Show the track artwork.',
            'default' => true,
        ],
    ],
];
