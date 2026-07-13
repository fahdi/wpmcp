<?php

namespace WPMCP\Tools\Elementor;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Data-driven catalog of the widget types generate-widget supports: for each
 * type, which input keys are required and how to build Elementor's real
 * settings array (its actual control keys) from validated input. Kept as one
 * focused file so adding a widget type later is a data change here, not a
 * change to the tool itself.
 */
class Widget_Schema
{
    /**
     * One entry per supported widget type:
     *  - required: input keys that must be present to build settings.
     *  - build: maps validated input into Elementor's real settings keys,
     *    applying defaults for anything the caller did not supply.
     *
     * @return array<string, array{required: array<int, string>, build: callable}>
     */
    private static function catalog(): array
    {
        return [
            'heading' => [
                'required' => ['title'],
                'build'    => static function (array $input): array {
                    return [
                        'title'       => (string) $input['title'],
                        'header_size' => (string) ($input['header_size'] ?? 'h2'),
                        'align'       => (string) ($input['align'] ?? 'left'),
                    ];
                },
            ],
            'text-editor' => [
                'required' => ['editor'],
                'build'    => static function (array $input): array {
                    return [
                        'editor' => (string) $input['editor'],
                    ];
                },
            ],
            'button' => [
                'required' => ['text'],
                'build'    => static function (array $input): array {
                    $link = is_array($input['link'] ?? null) ? $input['link'] : [];

                    return [
                        'text'  => (string) $input['text'],
                        'link'  => [
                            'url' => (string) ($link['url'] ?? ''),
                        ],
                        'align' => (string) ($input['align'] ?? 'center'),
                    ];
                },
            ],
            'image' => [
                'required' => ['url'],
                'build'    => static function (array $input): array {
                    return [
                        'image' => [
                            'id'  => (int) ($input['id'] ?? 0),
                            'url' => (string) $input['url'],
                        ],
                        'align' => (string) ($input['align'] ?? 'center'),
                    ];
                },
            ],
        ];
    }

    /** @return array<int, string> */
    public static function supported_types(): array
    {
        return array_keys(self::catalog());
    }

    public static function supports(string $widget_type): bool
    {
        return isset(self::catalog()[ $widget_type ]);
    }

    /** @return array<int, string> */
    public static function required_keys(string $widget_type): array
    {
        return self::catalog()[ $widget_type ]['required'] ?? [];
    }

    /** @return array<int, string> keys from required_keys() missing from $input. */
    public static function missing_required_keys(string $widget_type, array $input): array
    {
        return array_values(array_filter(
            self::required_keys($widget_type),
            static fn (string $key) => ! array_key_exists($key, $input)
        ));
    }

    /** Build Elementor's real settings array for $widget_type from validated $input. */
    public static function build_settings(string $widget_type, array $input): array
    {
        $entry = self::catalog()[ $widget_type ] ?? null;

        if (null === $entry) {
            return [];
        }

        return ($entry['build'])($input);
    }
}
