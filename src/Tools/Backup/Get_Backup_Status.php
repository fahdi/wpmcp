<?php

namespace WPMCP\Tools\Backup;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: return a backup job's current record (status, result/error,
 * timestamps) by id. Does not mutate the site.
 */
class Get_Backup_Status
{
    public function handle(array $args)
    {
        $job_id = (int) ($args['job_id'] ?? 0);
        $job    = Backup_Job_Store::get($job_id);

        if (null === $job) {
            return new \WP_Error('wpmcp_backup_job_not_found', "No backup job found with id \"{$job_id}\".");
        }

        return $job;
    }
}
