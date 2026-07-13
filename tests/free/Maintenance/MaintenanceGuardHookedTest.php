<?php

namespace WPMCP\Tests\Free\Maintenance;

use WPMCP\Maintenance\Maintenance_Guard;

class MaintenanceGuardHookedTest extends \WP_UnitTestCase
{
    public function test_maintenance_guard_enforce_is_hooked_to_template_redirect(): void
    {
        $found = false;

        foreach ($GLOBALS['wp_filter']['template_redirect']->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $function = $callback['function'];
                if (is_array($function) && $function[0] instanceof Maintenance_Guard && 'enforce' === $function[1]) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found, 'Expected Maintenance_Guard::enforce to be hooked to template_redirect');
    }
}
