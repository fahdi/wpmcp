<?php

namespace WPMCP\Tools\Blocks;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only utility: convert raw HTML into Gutenberg block markup. This
 * class only validates the input argument and shapes the tool result;
 * Html_To_Blocks_Converter does the actual DOM walking and mapping. A pure
 * transform, not a database write, so it never touches Safe_Mutation; to
 * save the resulting markup to a post use the existing update-blocks tool.
 */
class Convert_Html_To_Blocks
{
    public function handle(array $args): array
    {
        $html = (string) ($args['html'] ?? '');
        if ('' === trim($html)) {
            throw new \InvalidArgumentException('HTML content is required.');
        }

        return ['markup' => Html_To_Blocks_Converter::convert($html)];
    }
}
