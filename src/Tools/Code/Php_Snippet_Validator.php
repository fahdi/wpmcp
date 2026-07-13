<?php

namespace WPMCP\Tools\Code;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Static analysis guardrail for arbitrary PHP snippets. Never executes,
 * `eval`s, or `include`s the given code; syntax is checked by tokenizing the
 * source with token_get_all(..., TOKEN_PARSE), which raises a ParseError for
 * malformed code without running it. Safety heuristics are pattern-based
 * findings surfaced as warnings, `safe` reflects whether any critical
 * construct (code execution, request-driven execution, obfuscation) was
 * found; this class never blocks parsing, never writes anything, and never
 * runs the scanned code.
 */
class Php_Snippet_Validator
{
    /**
     * Function names whose mere presence is a critical safety finding:
     * arbitrary code/command execution primitives.
     */
    private const DANGEROUS_FUNCTIONS = [
        'eval', 'exec', 'system', 'shell_exec', 'passthru', 'proc_open',
        'popen', 'assert', 'create_function',
    ];

    /** Functions that write or delete filesystem paths. */
    private const FILESYSTEM_WRITE_FUNCTIONS = [
        'file_put_contents', 'unlink', 'fwrite',
    ];

    /** Functions used to decode obfuscated payloads before execution. */
    private const DECODER_FUNCTIONS = ['base64_decode', 'gzinflate', 'gzuncompress', 'str_rot13', 'convert_uudecode'];

    private const SUPERGLOBALS = ['_GET', '_POST', '_REQUEST'];

    public static function validate(string $code): array
    {
        [$syntax_valid, $errors] = self::check_syntax($code);
        $warnings                = self::scan_for_findings($code);

        $safe = true;
        foreach ($warnings as $warning) {
            if ('critical' === $warning['severity']) {
                $safe = false;
                break;
            }
        }

        return [
            'syntax_valid' => $syntax_valid,
            'errors'       => $errors,
            'warnings'     => $warnings,
            'safe'         => $safe,
        ];
    }

    /**
     * @return array{0: bool, 1: array}
     */
    private static function check_syntax(string $code): array
    {
        $source = self::ensure_php_tag($code);

        try {
            token_get_all($source, TOKEN_PARSE);
        } catch (\ParseError $e) {
            return [false, [
                [
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                ],
            ]];
        }

        return [true, []];
    }

    private static function ensure_php_tag(string $code): string
    {
        if (0 === strpos(ltrim($code), '<?php')) {
            return $code;
        }
        return '<?php ' . $code;
    }

    /**
     * Pattern-based safety heuristics. Never parses/executes the snippet;
     * this only matches literal tokens against a denylist and reports the
     * line each match occurred on.
     *
     * @return array{severity: string, message: string, line: int}[]
     */
    private static function scan_for_findings(string $code): array
    {
        $findings = [];
        $lines    = explode("\n", $code);

        foreach ($lines as $i => $line) {
            $line_no = $i + 1;

            foreach (self::DANGEROUS_FUNCTIONS as $fn) {
                if (self::matches_call($line, $fn)) {
                    $findings[] = [
                        'severity' => 'critical',
                        'message'  => "Dangerous construct: {$fn}() allows arbitrary code or command execution.",
                        'line'     => $line_no,
                    ];
                }
            }

            foreach (self::FILESYSTEM_WRITE_FUNCTIONS as $fn) {
                if (self::matches_call($line, $fn)) {
                    $findings[] = [
                        'severity' => 'warning',
                        'message'  => "Filesystem write: {$fn}() can write or delete arbitrary paths.",
                        'line'     => $line_no,
                    ];
                }
            }

            foreach (self::DECODER_FUNCTIONS as $fn) {
                if (self::matches_call($line, $fn)) {
                    $findings[] = [
                        'severity' => 'warning',
                        'message'  => "Obfuscation decoder: {$fn}() is often used to hide malicious payloads.",
                        'line'     => $line_no,
                    ];
                }
            }

            if (preg_match('/`[^`]*`/', $line)) {
                $findings[] = [
                    'severity' => 'critical',
                    'message'  => 'Backtick operator executes a shell command.',
                    'line'     => $line_no,
                ];
            }

            foreach (self::SUPERGLOBALS as $superglobal) {
                if (false !== strpos($line, '$' . $superglobal)) {
                    $findings[] = [
                        'severity' => 'warning',
                        'message'  => "Request-driven input: \${$superglobal} is read directly from the HTTP request.",
                        'line'     => $line_no,
                    ];
                }
            }

            if (preg_match('/wp_remote_|curl_init/i', $line)) {
                $findings[] = [
                    'severity' => 'warning',
                    'message'  => 'Outbound HTTP request to a potentially external host.',
                    'line'     => $line_no,
                ];
            }
        }

        return $findings;
    }

    private static function matches_call(string $line, string $function_name): bool
    {
        return (bool) preg_match('/(?<![\w>:$])' . preg_quote($function_name, '/') . '\s*\(/i', $line);
    }
}
