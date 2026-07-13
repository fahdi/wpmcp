<?php

namespace WPMCP\Tools\Structure;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: enumerate the shortcode tags registered in the global
 * $shortcode_tags array, each reduced to its tag name and a short
 * description of the callback where resolvable (function name, or
 * Class::method / Class->method for a class callback). An optional
 * 'search' (substring match against the tag name) narrows the result.
 */
class List_Shortcodes
{
    public function handle(array $args): array
    {
        global $shortcode_tags;

        $search = isset($args['search']) ? (string) $args['search'] : '';

        $tags = is_array($shortcode_tags) ? array_keys($shortcode_tags) : [];
        sort($tags);

        $shortcodes = [];
        foreach ($tags as $tag) {
            if ('' !== $search && false === strpos($tag, $search)) {
                continue;
            }

            $shortcodes[] = [
                'tag'      => $tag,
                'callback' => $this->describe_callback($shortcode_tags[ $tag ]),
            ];
        }

        return ['shortcodes' => $shortcodes];
    }

    private function describe_callback($callback): string
    {
        if (is_string($callback)) {
            return $callback;
        }

        if (is_array($callback) && 2 === count($callback)) {
            [$object_or_class, $method] = $callback;
            $class     = is_object($object_or_class) ? get_class($object_or_class) : (string) $object_or_class;
            $separator = is_object($object_or_class) ? '->' : '::';
            return $class . $separator . $method;
        }

        if (is_object($callback) && ! ($callback instanceof \Closure)) {
            return get_class($callback);
        }

        if ($callback instanceof \Closure) {
            return 'Closure';
        }

        return '';
    }
}
