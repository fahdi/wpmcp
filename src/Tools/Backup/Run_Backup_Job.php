<?php

namespace WPMCP\Tools\Backup;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The WP-Cron executor for a queued backup job. Trigger_Backup schedules a
 * single event on self::HOOK with the job id as its only argument;
 * Plugin::boot() hooks self::HOOK to [new Run_Backup_Job(), 'handle'] so
 * WordPress invokes it on the next cron run.
 */
class Run_Backup_Job
{
    public const HOOK = 'wpmcp_run_backup_job';
}
