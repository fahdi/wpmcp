<?php

if (! defined('ABSPATH')) {
    exit;
}

/** Curated catalog definition for the Elementor Pro 'form' widget (issue #59). */
return [
    'type' => 'form',
    'title' => 'Form',
    'category' => 'pro-elements',
    'purpose' => 'Front-end form with configurable fields and actions (contact, lead capture).',
    'keywords' => [
        'contact',
        'lead',
        'input',
        'submit',
    ],
    'requires' => 'elementor-pro',
    'params' => [
        'form_name' => [
            'type' => 'string',
            'description' => 'Internal name of the form.',
            'default' => 'New Form',
        ],
        'form_fields' => [
            'type' => 'repeater',
            'description' => 'The form fields.',
            'fields' => [
                'custom_id' => [
                    'type' => 'string',
                    'description' => 'Field id used in submissions and email shortcodes.',
                ],
                'field_type' => [
                    'type' => 'choice',
                    'description' => 'The field type.',
                    'enum' => [
                        'text',
                        'email',
                        'textarea',
                        'url',
                        'tel',
                        'radio',
                        'select',
                        'checkbox',
                        'acceptance',
                        'number',
                        'date',
                        'time',
                        'upload',
                        'password',
                        'html',
                        'hidden',
                    ],
                    'default' => 'text',
                ],
                'field_label' => [
                    'type' => 'string',
                    'description' => 'The visible field label.',
                ],
                'placeholder' => [
                    'type' => 'string',
                    'description' => 'Placeholder text.',
                ],
                'required' => [
                    'type' => 'bool',
                    'description' => 'Whether the field is required.',
                    'on' => 'true',
                    'off' => '',
                ],
            ],
        ],
        'button_text' => [
            'type' => 'string',
            'description' => 'The submit button label.',
            'default' => 'Send',
        ],
    ],
];
