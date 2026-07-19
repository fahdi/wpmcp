<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor Pro 'countdown' widget (issue #59). */
return [
    'type' => 'countdown',
    'title' => 'Countdown',
    'category' => 'pro-elements',
    'purpose' => 'Countdown timer to a due date or an evergreen interval.',
    'keywords' => [
        'timer',
        'launch',
        'urgency',
        'deadline',
    ],
    'requires' => 'elementor-pro',
    'params' => [
        'countdown_type' => [
            'type' => 'choice',
            'description' => 'Fixed due date or evergreen per-visitor timer.',
            'enum' => [
                'due_date',
                'evergreen',
            ],
            'default' => 'due_date',
        ],
        'due_date' => [
            'type' => 'string',
            'description' => 'The target date-time (YYYY-MM-DD HH:MM).',
        ],
        'show_days' => [
            'type' => 'bool',
            'description' => 'Show the days unit.',
            'default' => true,
        ],
        'show_hours' => [
            'type' => 'bool',
            'description' => 'Show the hours unit.',
            'default' => true,
        ],
        'show_minutes' => [
            'type' => 'bool',
            'description' => 'Show the minutes unit.',
            'default' => true,
        ],
        'show_seconds' => [
            'type' => 'bool',
            'description' => 'Show the seconds unit.',
            'default' => true,
        ],
    ],
];
