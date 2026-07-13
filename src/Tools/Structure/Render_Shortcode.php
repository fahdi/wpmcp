<?php

namespace WPMCP\Tools\Structure;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Utility: render a shortcode string (e.g. "[gallery ids=\"1,2\"]") via
 * do_shortcode(). This executes the shortcode's own registered callback,
 * exactly as WordPress would when rendering post content; do_shortcode()
 * only ever invokes tags present in the global $shortcode_tags registry, so
 * this cannot execute arbitrary PHP. Input is required to actually look
 * like shortcode markup, i.e. contain an opening "[", refusing plain
 * strings that were clearly never meant to be parsed as a shortcode.
 */
class Render_Shortcode
{
    public function handle(array $args): array
    {
        $shortcode = isset($args['shortcode']) ? (string) $args['shortcode'] : '';

        if ('' === trim($shortcode)) {
            throw new \InvalidArgumentException('"shortcode" is required.');
        }

        if (false === strpos($shortcode, '[')) {
            throw new \InvalidArgumentException('"shortcode" does not look like shortcode markup.');
        }

        return ['html' => do_shortcode($shortcode)];
    }
}
