<?php

namespace WPMCP\Tools\Cli;

use WPMCP\Governance\Governance_Audit_Log;
use WPMCP\Identity\Identity_Context;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The run-wp-cli tool handler (issue #44): runs a guarded, allowlisted wp-cli
 * subcommand and returns its stdout/stderr/exit code. This class composes
 * every Wp_Cli_Guard check, in order, and is the only place that decides
 * whether a command reaches the executor:
 *
 *  1. Wp_Cli_Guard::is_enabled()             - default OFF
 *  2. Wp_Cli_Guard::is_allowed_on_environment() - refuses production
 *  3. Wp_Cli_Guard::is_allowed_subcommand()  - allowlist, deny by default
 *  4. Wp_Cli_Guard::validate_args()          - shell metacharacter/NUL check
 *  5. Wp_Cli_Guard::validate_flags()         - safe-flag allowlist, deny by
 *                                              default on every "-"-prefixed
 *                                              token anywhere in the argv
 *  6. Wp_Cli_Guard::resolve_binary()         - locates the wp binary
 *
 * Every attempt, allowed or denied, is recorded via Governance_Audit_Log,
 * same as the ordinary permission-check audit trail (Registrar::record_audit):
 * only the ability name, active identity, and allow/deny outcome are logged,
 * never the command/argv itself, so a wp-cli invocation touching a secret
 * (e.g. `option get some_api_key`) never leaks that secret into the log.
 *
 * The actual subprocess run is injected as a callable (default: the real
 * Wp_Cli_Executor::run), so tests can supply a fake that records the argv it
 * was called with and returns a canned result, without ever spawning a
 * process. This is the seam the guard-behavior tests in
 * tests/free/Cli/RunWpCliTest.php exercise.
 *
 * Full architecture writeup, including exactly what is CI-tested versus
 * production-only (the real proc_open round-trip) and flags left for the
 * adversarial security review: .superpowers/sdd/issue-44-report.md.
 */
class Run_Wp_Cli
{
    /** @var callable */
    private $executor;

    public function __construct(?callable $executor = null)
    {
        $this->executor = $executor ?? [Wp_Cli_Executor::class, 'run'];
    }

    public function handle(array $args): array
    {
        $command = isset($args['command']) ? trim((string) $args['command']) : '';
        if ('' === $command) {
            throw new \InvalidArgumentException('A wp-cli command is required.');
        }

        $subcommand_argv = self::split_command($command);

        try {
            $this->guard($subcommand_argv);
        } catch (\RuntimeException $e) {
            $this->audit(false);
            throw $e;
        }

        $binary = Wp_Cli_Guard::resolve_binary();
        // guard() above already confirmed resolve_binary() succeeds; a
        // WP_Error here would mean it changed between calls (e.g. a filter
        // with side effects), so re-check defensively rather than assume.
        if (is_wp_error($binary)) {
            $this->audit(false);
            throw new \RuntimeException($binary->get_error_message());
        }

        $full_argv = array_merge([$binary], $subcommand_argv);

        $result = ($this->executor)($full_argv, Wp_Cli_Executor::DEFAULT_TIMEOUT_SECONDS);

        $this->audit(true);

        return [
            'stdout'    => (string) ($result['stdout'] ?? ''),
            'stderr'    => (string) ($result['stderr'] ?? ''),
            'exit_code' => (int) ($result['exit_code'] ?? -1),
            'timed_out' => (bool) ($result['timed_out'] ?? false),
        ];
    }

    /**
     * Run every guard in order, throwing a RuntimeException with the
     * relevant message on the first one that fails. Never calls the
     * executor.
     *
     * @param string[] $subcommand_argv
     */
    private function guard(array $subcommand_argv): void
    {
        if (! Wp_Cli_Guard::is_enabled()) {
            throw new \RuntimeException(
                'WP-CLI execution is disabled. Enable it with the WPMCP_ALLOW_WP_CLI constant or the wpmcp_allow_wp_cli filter.'
            );
        }

        if (! Wp_Cli_Guard::is_allowed_on_environment()) {
            throw new \RuntimeException(
                'WP-CLI execution is refused on a production environment. Set WPMCP_ALLOW_WP_CLI_ON_PRODUCTION or the wpmcp_allow_wp_cli_on_production filter to override.'
            );
        }

        if (! Wp_Cli_Guard::is_allowed_subcommand($subcommand_argv)) {
            throw new \RuntimeException(
                'This wp-cli subcommand is not on the allowlist. Extend it with the wpmcp_wp_cli_allowlist filter.'
            );
        }

        $args_valid = Wp_Cli_Guard::validate_args($subcommand_argv);
        if (is_wp_error($args_valid)) {
            throw new \RuntimeException($args_valid->get_error_message());
        }

        $flags_valid = Wp_Cli_Guard::validate_flags($subcommand_argv);
        if (is_wp_error($flags_valid)) {
            throw new \RuntimeException($flags_valid->get_error_message());
        }

        $binary = Wp_Cli_Guard::resolve_binary();
        if (is_wp_error($binary)) {
            throw new \RuntimeException($binary->get_error_message());
        }
    }

    /**
     * Record this attempt to Governance_Audit_Log. Deliberately logs only
     * the ability name, active identity, and allow/deny outcome, mirroring
     * Registrar::record_audit(): never the command string or argv, so no
     * wp-cli argument (which may contain a secret value) ever reaches the
     * audit log.
     */
    private function audit(bool $allowed): void
    {
        try {
            $identity = Identity_Context::current() ?? 'none';
            Governance_Audit_Log::record('wpmcp/run-wp-cli', $identity, $allowed);
        } catch (\Throwable $e) {
            // Auditing must never break (or block) the command outcome it observes.
        }
    }

    /**
     * Split a command string into argv words. A plain whitespace split is
     * sufficient (and deliberately not a shell-style quote-aware parser):
     * Wp_Cli_Guard::validate_args() rejects shell metacharacters outright,
     * so there is no quoting syntax this tool needs to understand or
     * support in the first place.
     *
     * @return string[]
     */
    private static function split_command(string $command): array
    {
        $parts = preg_split('/\s+/', trim($command));
        return is_array($parts) ? $parts : [];
    }
}
