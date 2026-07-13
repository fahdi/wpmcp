<?php

namespace WPMCP\Tests\Free\Cli;

use WPMCP\Tools\Cli\Wp_Cli_Executor;

/**
 * Issue #44 security review, M2 (MEDIUM): on timeout, Wp_Cli_Executor used
 * to call proc_terminate() on only the direct wp-cli child, so any
 * grandchild process it spawned (e.g. an `ssh` child process from a wp-cli
 * command using --ssh, or a package subprocess) could survive the kill and
 * leak, accumulating over repeated timed-out calls (partial DoS).
 *
 * Wp_Cli_Executor::kill_process_group() is the pure decision logic behind
 * the best-effort group kill: given the child PID and injectable
 * "get process group" / "send signal" callables (so no real process or
 * posix_kill call is ever needed in tests), it decides whether sending the
 * kill signal to the child's WHOLE process group is safe, and only does so
 * when it can positively confirm the child's process group differs from
 * this PHP process's own group. Signaling a process group this code itself
 * belongs to would kill the PHP process (and whatever spawned it) instead
 * of just the wp-cli subtree, so that case must be skipped, not attempted.
 */
class WpCliExecutorKillTest extends \WP_UnitTestCase
{
    public function test_signals_the_child_group_when_it_differs_from_our_own(): void
    {
        $signaled = [];
        $kill     = function (int $pid, int $signal) use (&$signaled): bool {
            $signaled[] = ['pid' => $pid, 'signal' => $signal];
            return true;
        };
        $get_pgid = function (int $pid): int {
            // Child pid 999 lives in group 500; our own pid (getmypid())
            // is mapped to a different group (600) by the fake below.
            return 999 === $pid ? 500 : 600;
        };

        Wp_Cli_Executor::kill_process_group(999, $get_pgid, $kill);

        $this->assertCount(1, $signaled);
        $this->assertSame(-500, $signaled[0]['pid'], 'Must signal the NEGATIVE pgid to target the whole group, not just the one PID.');
        $this->assertSame(9, $signaled[0]['signal'], 'Must send SIGKILL (9).');
    }

    public function test_refuses_to_signal_when_child_shares_our_own_process_group(): void
    {
        // This is the realistic default on this platform (verified: a
        // proc_open child inherits the parent PHP process's own group when
        // no new session/group was started for it), so signaling "the
        // group" would kill the PHP process (and its parent shell) too.
        // The safe behavior is to skip the group signal entirely in that
        // case and rely on the already-existing proc_terminate() of the
        // single direct child.
        $signaled = [];
        $kill     = function (int $pid, int $signal) use (&$signaled): bool {
            $signaled[] = ['pid' => $pid, 'signal' => $signal];
            return true;
        };
        $get_pgid = function (int $pid): int {
            return 700; // same group for every pid, including our own
        };

        Wp_Cli_Executor::kill_process_group(999, $get_pgid, $kill);

        $this->assertCount(0, $signaled, 'Must never signal a group this process itself belongs to.');
    }

    public function test_refuses_to_signal_when_group_lookup_fails(): void
    {
        $signaled = [];
        $kill     = function (int $pid, int $signal) use (&$signaled): bool {
            $signaled[] = ['pid' => $pid, 'signal' => $signal];
            return true;
        };
        $get_pgid = function (int $pid): bool {
            return false; // posix_getpgid() returns false on lookup failure
        };

        Wp_Cli_Executor::kill_process_group(999, $get_pgid, $kill);

        $this->assertCount(0, $signaled, 'Must never guess a group to signal when lookup fails.');
    }

    public function test_is_a_no_op_when_posix_is_unavailable(): void
    {
        $signaled  = [];
        $kill      = function (int $pid, int $signal) use (&$signaled): bool {
            $signaled[] = ['pid' => $pid, 'signal' => $signal];
            return true;
        };

        // Passing null for both callables simulates a platform where the
        // posix extension is not loaded: kill_process_group() must fall
        // back to a documented no-op rather than erroring.
        Wp_Cli_Executor::kill_process_group(999, null, $kill, false);

        $this->assertCount(0, $signaled);
    }
}
