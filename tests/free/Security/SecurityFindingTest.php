<?php

namespace WPMCP\Tests\Free\Security;

use WPMCP\Tools\Security\Security_Finding;

class SecurityFindingTest extends \WP_UnitTestCase
{
    public function test_make_returns_the_canonical_finding_array(): void
    {
        $finding = Security_Finding::make(
            'integrity_modified',
            'integrity',
            'Modified core file',
            'critical',
            'wp-load.php',
            'Checksum mismatch.',
            'Reinstall core.'
        );

        $this->assertSame('integrity_modified', $finding['id']);
        $this->assertSame('integrity', $finding['category']);
        $this->assertSame('Modified core file', $finding['label']);
        $this->assertSame('critical', $finding['status']);
        $this->assertSame('wp-load.php', $finding['value']);
        $this->assertSame('Checksum mismatch.', $finding['message']);
        $this->assertSame('Reinstall core.', $finding['recommendation']);
    }

    public function test_recommendation_defaults_to_empty(): void
    {
        $finding = Security_Finding::make('x', 'hardening', 'X', 'pass', true, 'ok');

        $this->assertSame('', $finding['recommendation']);
    }
}
