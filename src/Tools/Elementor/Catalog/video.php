<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor 'video' widget (issue #59). */
return [
    'type' => 'video',
    'title' => 'Video',
    'category' => 'basic',
    'purpose' => 'Embed a YouTube, Vimeo, Dailymotion, VideoPress, or self-hosted video.',
    'keywords' => [
        'youtube',
        'vimeo',
        'embed',
        'player',
        'movie',
    ],
    'requires' => 'elementor',
    'params' => [
        'video_type' => [
            'type' => 'choice',
            'description' => 'Video source.',
            'enum' => [
                'youtube',
                'vimeo',
                'dailymotion',
                'videopress',
                'hosted',
            ],
            'default' => 'youtube',
        ],
        'youtube_url' => [
            'type' => 'string',
            'description' => 'YouTube video URL (used when video_type is youtube).',
        ],
        'vimeo_url' => [
            'type' => 'string',
            'description' => 'Vimeo video URL (used when video_type is vimeo).',
        ],
        'dailymotion_url' => [
            'type' => 'string',
            'description' => 'Dailymotion video URL (used when video_type is dailymotion).',
        ],
        'hosted_url' => [
            'type' => 'media',
            'description' => 'Self-hosted video file (used when video_type is hosted).',
        ],
        'autoplay' => [
            'type' => 'bool',
            'description' => 'Start playing automatically.',
            'default' => false,
        ],
        'mute' => [
            'type' => 'bool',
            'description' => 'Start muted.',
            'default' => false,
        ],
        'loop' => [
            'type' => 'bool',
            'description' => 'Loop playback.',
            'default' => false,
        ],
        'controls' => [
            'type' => 'bool',
            'description' => 'Show player controls.',
            'default' => true,
        ],
        'start' => [
            'type' => 'integer',
            'description' => 'Start time in seconds.',
        ],
        'end' => [
            'type' => 'integer',
            'description' => 'End time in seconds (YouTube and self-hosted only).',
        ],
    ],
];
