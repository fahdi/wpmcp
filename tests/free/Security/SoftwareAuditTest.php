<?php

namespace WPMCP\Tests\Free\Security;

use WPMCP\Tools\Security\Software_Audit;

class SoftwareAuditTest extends \WP_UnitTestCase
{
    private function audit(): Software_Audit
    {
        return new Software_Audit();
    }

    private function ids(array $findings): array
    {
        return array_map(static fn($finding) => $finding['id'], $findings);
    }

    public function test_core_update_available_is_warning(): void
    {
        $this->assertSame('warning', $this->audit()->evaluate_core_update(true, '6.4', '6.9')['status']);
        $this->assertSame('pass', $this->audit()->evaluate_core_update(false, '6.9', '6.9')['status']);
    }

    public function test_outdated_components_each_warn(): void
    {
        $updates = [
            ['name' => 'Foo', 'current' => '1.0', 'new' => '2.0'],
            ['name' => 'Bar', 'current' => '3.1', 'new' => '3.2'],
        ];

        $findings = $this->audit()->evaluate_updates($updates, 'plugin');

        $this->assertCount(2, $findings);
        $this->assertSame('warning', $findings[0]['status']);
        $this->assertSame('software', $findings[0]['category']);
    }

    public function test_no_updates_yields_no_findings(): void
    {
        $this->assertSame([], $this->audit()->evaluate_updates([], 'plugin'));
    }

    public function test_abandoned_plugins_each_warn(): void
    {
        $findings = $this->audit()->evaluate_abandoned(['old-plugin', 'dead-plugin']);

        $this->assertSame(['software_abandoned', 'software_abandoned'], $this->ids($findings));
        $this->assertSame('warning', $findings[0]['status']);
    }

    public function test_inactive_components_are_info(): void
    {
        $this->assertSame('info', $this->audit()->evaluate_inactive(3)['status']);
        $this->assertSame('pass', $this->audit()->evaluate_inactive(0)['status']);
    }
}
