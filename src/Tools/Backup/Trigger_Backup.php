<?php

namespace WPMCP\Tools\Backup;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Kick off an async backup: create a queued job record and schedule a
 * single WP-Cron event (Run_Backup_Job::HOOK, passed the job id) that will
 * actually produce the backup artifact and flip the job to completed/failed.
 *
 * This only reads site data and writes to the job-store option and (once the
 * scheduled event runs) a backup artifact file; it never mutates user
 * content, so it is not routed through Safe_Mutation and does not touch the
 * safety core. Governance and the capability gate (manage_options, checked
 * by the Registrar/Ability layer at registration) are the only gates.
 */
class Trigger_Backup
{
    public function handle(array $args): array
    {
        $type  = isset($args['type']) ? (string) $args['type'] : 'full';
        $scope = isset($args['scope']) ? (string) $args['scope'] : 'all';

        $job = Backup_Job_Store::create($type, $scope);

        wp_schedule_single_event(time(), Run_Backup_Job::HOOK, [$job['id']]);

        return [
            'job_id' => $job['id'],
            'status' => $job['status'],
        ];
    }
}
