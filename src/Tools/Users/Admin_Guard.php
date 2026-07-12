<?php

namespace WPMCP\Tools\Users;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Shared "is this an admin-capable user?" check for the user tools.
 *
 * The test is capability-based, not a role-name string match: any of these
 * capabilities means the account can manage the site or other users, so the
 * tools treat it as protected regardless of which role (or custom role)
 * granted them. Get_User uses this for its is_admin flag; Update_User uses it
 * to refuse editing such accounts.
 */
class Admin_Guard
{
    private const ADMIN_CAPS = ['manage_options', 'edit_users', 'promote_users', 'delete_users'];

    public static function is_admin_user(\WP_User $user): bool
    {
        foreach (self::ADMIN_CAPS as $cap) {
            if ($user->has_cap($cap)) {
                return true;
            }
        }
        return false;
    }
}
