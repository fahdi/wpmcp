<?php

namespace WPMCP\Tools\Code;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: statically validate a PHP code snippet without executing it.
 * Delegates syntax checking and safety heuristics to Php_Snippet_Validator;
 * this class only validates the input argument and shapes the tool result.
 * Never eval()s, include()s, or otherwise runs the snippet, and writes
 * nothing, so this never touches Safe_Mutation.
 */
class Validate_Php_Snippet
{
    public function handle(array $args): array
    {
        $code = (string) ($args['code'] ?? '');
        if ('' === trim($code)) {
            throw new \InvalidArgumentException('A PHP code snippet is required.');
        }

        return Php_Snippet_Validator::validate($code);
    }
}
