<?php

namespace WPMCP\Tests\Free\Regression;

use WPMCP\Safety\{File_Backup, Rollback_Service, Snapshot_Store};
use WPMCP\Tools\Content\Delete_Post;
use WPMCP\Tools\Media\Delete_Media;
use WPMCP\Tools\Database\Database_Guard;
use WPMCP\Tools\Filesystem\{Filesystem_Guard, Read_File, Write_File};
use WPMCP\Tools\Security\Malware_Audit;

/**
 * Consolidated registry of the real, previously-fixed bugs this project must
 * never silently regress on. Each test here calls through the actual public,
 * already end-to-end-tested entry point (a Tool::handle() or a guard's public
 * method) for the specific fixed behavior, rather than re-deriving the deep
 * mechanism: the exhaustive unit coverage for each already lives in the file
 * referenced in that test's docblock. This file exists so the full list of
 * "must never come back" bugs is visible and locked in from one place.
 *
 * @group regression
 */
class KnownRegressionsRegistryTest extends \WP_UnitTestCase
{
    private array $cleanup_paths = [];

    protected function setUp(): void
    {
        parent::setUp();
        Snapshot_Store::install();
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanup_paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->cleanup_paths = [];
        parent::tearDown();
    }

    /**
     * A force-deleted post's row is gone entirely, so a naive restore that
     * only re-inserts title/content/status silently reconstructs the rest
     * from wp_insert_post()'s defaults: a force-deleted custom post type
     * came back as a plain 'post' at whatever id WordPress's auto-increment
     * happened to assign, not the original.
     *
     * Full mechanism coverage: tests/free/Content/DeletePostTest.php
     * (test_force_delete_rollback_restores_type_author_parent_and_slug_for_a_cpt).
     */
    public function test_force_deleted_post_keeps_its_post_type_and_id_on_rollback(): void
    {
        register_post_type('wpmcp_regr_cpt', ['public' => true, 'supports' => ['title']]);
        add_filter('wpmcp_enable_delete_post', '__return_true');

        $id = self::factory()->post->create(['post_type' => 'wpmcp_regr_cpt', 'post_title' => 'Keep my type']);

        $out = (new Delete_Post())->handle(['post_id' => $id, 'force' => true, 'confirm' => true, 'session_id' => 'regr']);
        $this->assertNull(get_post($id));

        $this->assertTrue(Rollback_Service::restore_operation($out['operation_id']));

        $restored = get_post($id);
        $this->assertNotNull($restored, 'The post must be resurrected at its original id.');
        $this->assertSame($id, $restored->ID);
        $this->assertSame('wpmcp_regr_cpt', $restored->post_type);

        unregister_post_type('wpmcp_regr_cpt');
    }

    /**
     * A force-deleted attachment unlinks its physical files as part of
     * wp_delete_attachment(..., true). A rollback that only restores the DB
     * row leaves a dangling attachment record pointing at files that no
     * longer exist. Delete_Media must back up the files before deleting and
     * Rollback_Service must restore them after resurrecting the record.
     *
     * Full mechanism coverage: tests/free/Media/DeleteMediaTest.php
     * (test_force_delete_is_safe_wrapped_and_rollback_resurrects_attachment_and_files).
     */
    public function test_force_deleted_media_restores_its_physical_files_on_rollback(): void
    {
        add_filter('wpmcp_enable_delete_media', '__return_true');

        $uploads = wp_upload_dir();
        $main    = trailingslashit($uploads['path']) . 'wpmcp-regr-media.jpg';
        wp_mkdir_p(dirname($main));
        file_put_contents($main, 'original-bytes');
        $this->cleanup_paths[] = $main;

        $id = self::factory()->attachment->create_object([
            'file'           => $main,
            'post_mime_type' => 'image/jpeg',
            'post_title'     => 'Regression Sunset',
        ]);

        $out = (new Delete_Media())->handle(['media_id' => $id, 'confirm' => true, 'force' => true, 'session_id' => 'regr']);
        $this->assertNull(get_post($id));
        $this->assertFileDoesNotExist($main);

        $this->assertTrue(Rollback_Service::restore_operation($out['operation_id']));

        $this->assertNotNull(get_post($id));
        $this->assertFileExists($main);
        $this->assertSame('original-bytes', file_get_contents($main));

        File_Backup::delete_backup_dir($out['operation_id']);
    }

    /**
     * restore_session() used to dedupe restore rows on "{object_type}:
     * {object_id}" using the DB object_id column, which is always 0 for
     * 'option' snapshots (an option's real identity lives only inside the
     * serialized blob). A session touching two or more distinct options
     * collapsed onto the same key and silently restored only the first one.
     *
     * Full mechanism coverage: tests/free/Safety/RollbackServiceTest.php
     * (test_restore_session_unwinds_multiple_distinct_options).
     */
    public function test_multi_option_session_rollback_restores_every_option(): void
    {
        update_option('wpmcp_regr_opt_a', 'Original A');
        update_option('wpmcp_regr_opt_b', 'Original B');
        update_option('wpmcp_regr_opt_c', 'Original C');

        $session = 'regr-multi-option';
        foreach (['wpmcp_regr_opt_a' => 'Changed A', 'wpmcp_regr_opt_b' => 'Changed B', 'wpmcp_regr_opt_c' => 'Changed C'] as $name => $to) {
            \WPMCP\Safety\Safe_Mutation::run(
                ['object_type' => 'option', 'object_id' => $name, 'session_id' => $session, 'tool_name' => 'update-settings', 'args' => []],
                function () use ($name, $to): void {
                    update_option($name, $to);
                }
            );
        }

        $this->assertSame(3, Rollback_Service::restore_session($session));
        $this->assertSame('Original A', get_option('wpmcp_regr_opt_a'));
        $this->assertSame('Original B', get_option('wpmcp_regr_opt_b'));
        $this->assertSame('Original C', get_option('wpmcp_regr_opt_c'));

        delete_option('wpmcp_regr_opt_a');
        delete_option('wpmcp_regr_opt_b');
        delete_option('wpmcp_regr_opt_c');
    }

    /**
     * normalize_sql() hardcodes backslash-as-escape. Under a server running
     * with NO_BACKSLASH_ESCAPES, MySQL does not treat `\` as an escape, so a
     * crafted literal ends one character earlier than the guard assumes and
     * live, unparsed file-access SQL after it executes for real. The guard's
     * raw pre-scan must catch this independent of sql_mode.
     *
     * Full mechanism coverage: tests/free/Database/DatabaseGuardTest.php
     * (test_rejects_confirmed_backslash_escape_desync_bypasses and related).
     */
    public function test_sql_guard_rejects_the_backslash_desync_file_access_bypass(): void
    {
        $result = Database_Guard::is_read_only_sql("SELECT 'a\\' , load_file('/etc/passwd') , 'b");

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('file_access_blocked', $result->get_error_code());
    }

    /**
     * Escape 1: a symlink whose leaf lives inside the sandbox but whose
     * target does not exist yet ("dangling") must not be usable to write
     * outside the WordPress install. resolve_path() previously trusted the
     * leaf's basename verbatim in the not-yet-existing branch.
     *
     * Full mechanism coverage: tests/free/Filesystem/WriteFileTest.php
     * (test_refuses_to_write_through_a_dangling_symlink_leaf).
     */
    public function test_filesystem_guard_blocks_writing_through_a_dangling_symlink(): void
    {
        add_filter('wpmcp_enable_fs_writes', '__return_true');
        $admin = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin);

        $rel_dir = 'wp-content/wpmcp-regr-fs-test';
        mkdir(ABSPATH . $rel_dir, 0777, true);

        $outside_target = sys_get_temp_dir() . '/wpmcp-regr-escape-' . uniqid() . '.txt';
        $link           = ABSPATH . $rel_dir . '/evil';

        if (! @symlink($outside_target, $link)) {
            @rmdir(ABSPATH . $rel_dir);
            $this->markTestSkipped('symlink() unavailable in this environment');
        }

        try {
            $this->expectException(\RuntimeException::class);
            (new Write_File())->handle(['path' => $rel_dir . '/evil', 'content' => 'pwned']);
        } finally {
            $this->assertFileDoesNotExist($outside_target);
            @unlink($link);
            @unlink($outside_target);
            @rmdir(ABSPATH . $rel_dir);
        }
    }

    /**
     * Escape 2: an in-tree symlink pointing outside the sandbox must not be
     * readable through. resolve_path() previously canonicalized via
     * realpath(), which follows symlinks, so the "inside" real path leaked
     * outside file content.
     *
     * Full mechanism coverage: tests/free/Filesystem/ReadFileTest.php
     * (test_rejects_reading_through_an_in_tree_symlink_to_outside_content).
     */
    public function test_filesystem_guard_blocks_reading_through_a_symlink_that_escapes_the_root(): void
    {
        $rel_dir = 'wp-content/wpmcp-regr-fs-read-test';
        mkdir(ABSPATH . $rel_dir, 0777, true);

        $outside = sys_get_temp_dir() . '/wpmcp-regr-read-' . uniqid() . '.txt';
        file_put_contents($outside, "outside-secret\n");

        $link = ABSPATH . $rel_dir . '/leak.txt';
        if (! @symlink($outside, $link)) {
            @unlink($outside);
            @rmdir(ABSPATH . $rel_dir);
            $this->markTestSkipped('symlink() unavailable in this environment');
        }

        try {
            $this->expectException(\RuntimeException::class);
            (new Read_File())->handle(['path' => $rel_dir . '/leak.txt']);
        } finally {
            @unlink($link);
            @unlink($outside);
            @rmdir(ABSPATH . $rel_dir);
        }
    }

    /**
     * Escape 3: Filesystem_Guard::is_protected() was write-only; Read_File
     * never called it, so reading "wp-config.php" returned raw DB
     * credentials and salts.
     *
     * Full mechanism coverage: tests/free/Filesystem/ReadFileTest.php
     * (test_refuses_to_read_a_protected_file).
     */
    public function test_filesystem_guard_blocks_reading_a_protected_file(): void
    {
        $this->assertTrue(Filesystem_Guard::is_protected(ABSPATH . 'wp-config.php'));

        $this->expectException(\RuntimeException::class);
        (new Read_File())->handle(['path' => 'wp-config.php']);
    }

    /**
     * The webshell-marker regex (WSO\s*[0-9.]*\s*shell) backtracked
     * quadratically on adversarial bait (a long run of "WSO" + whitespace
     * with no trailing "shell"), letting a single crafted file hang the
     * scanner. The bounded-quantifier fix must keep this fast.
     *
     * Full mechanism coverage: tests/free/Security/MalwareAuditScanCodeTest.php
     * (test_adversarial_wso_line_does_not_cause_quadratic_backtracking).
     */
    public function test_malware_scanner_does_not_hang_on_the_webshell_regex_bait(): void
    {
        $line = 'WSO' . str_repeat(' ', 65533);
        $code = "<?php\n{$line}\n";

        $start   = microtime(true);
        (new Malware_Audit())->scan_code($code, 'a.php');
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(1.0, $elapsed, 'The webshell-marker regex must not backtrack quadratically on adversarial bait.');
    }

    /**
     * A long base64-looking blob must never be echoed back whole in a
     * finding's snippet (info-disclosure risk if the blob itself carries
     * sensitive data); only its length is safe to report.
     *
     * Full mechanism coverage: tests/free/Security/MalwareAuditScanCodeTest.php
     * (test_long_base64_snippet_is_redacted_and_reports_length).
     */
    public function test_malware_scanner_redacts_long_base64_snippets(): void
    {
        $blob     = str_repeat('AB12cd', 60);
        $code     = "<?php\n\$x = '{$blob}';\n";
        $findings = (new Malware_Audit())->scan_code($code, 'a.php');

        $hit = null;
        foreach ($findings as $finding) {
            if ('malware_long_base64' === $finding['id']) {
                $hit = $finding;
                break;
            }
        }

        $this->assertNotNull($hit, 'Expected a malware_long_base64 finding.');
        $this->assertStringNotContainsString($blob, wp_json_encode($hit));
    }
}
