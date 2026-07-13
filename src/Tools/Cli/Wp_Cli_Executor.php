<?php

namespace WPMCP\Tools\Cli;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The ONLY class in this plugin that actually spawns a wp-cli process. Runs
 * a fully-resolved, already-guarded argv (binary + subcommand words) via
 * proc_open with the command given as an ARRAY, never a shell string: PHP's
 * proc_open bypasses the shell entirely when given an array, so there is no
 * shell metacharacter interpretation to worry about here (Wp_Cli_Guard's
 * character check is defense-in-depth on top of this, not the only guard).
 *
 * Enforces a wall-clock timeout, killing the process if it runs over, and
 * always returns stdout/stderr/exit code separately rather than a merged
 * blob. Run_Wp_Cli depends on this only through the injectable callable it
 * accepts (default [self::class, 'run']), so tests can assert the argv that
 * WOULD run without ever actually spawning a process.
 */
class Wp_Cli_Executor
{
    public const DEFAULT_TIMEOUT_SECONDS = 30;

    /**
     * @param string[] $argv Full argv INCLUDING the binary as element 0.
     *
     * @return array{stdout: string, stderr: string, exit_code: int, timed_out: bool}
     */
    public static function run(array $argv, int $timeout_seconds = self::DEFAULT_TIMEOUT_SECONDS): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // Array form: proc_open never invokes a shell to parse this, each
        // element is passed to the child process as a literal argv entry.
        $process = proc_open($argv, $descriptors, $pipes);

        if (! is_resource($process)) {
            return [
                'stdout'    => '',
                'stderr'    => 'Failed to start the wp-cli process.',
                'exit_code' => -1,
                'timed_out' => false,
            ];
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout    = '';
        $stderr    = '';
        $timed_out = false;
        $start     = microtime(true);

        while (true) {
            $status = proc_get_status($process);

            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);

            if (! $status['running']) {
                break;
            }

            if ((microtime(true) - $start) >= $timeout_seconds) {
                $timed_out = true;
                proc_terminate($process, 9);
                break;
            }

            usleep(20000);
        }

        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit_code = $timed_out ? -1 : (int) proc_close($process);
        if ($timed_out) {
            proc_close($process);
        }

        return [
            'stdout'    => $stdout,
            'stderr'    => $timed_out ? ($stderr . "\nProcess timed out after {$timeout_seconds} second(s) and was terminated.") : $stderr,
            'exit_code' => $exit_code,
            'timed_out' => $timed_out,
        ];
    }
}
