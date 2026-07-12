<?php

namespace WPMCP\Tests\Free\Performance;

use WPMCP\Tools\Performance\Server_Audit;

class ServerAuditTest extends \WP_UnitTestCase
{
    private Server_Audit $audit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->audit = new Server_Audit();
    }

    public function test_php_version_bands(): void
    {
        $this->assertSame('pass', $this->audit->evaluate_php_version('8.2.0')['status']);
        $this->assertSame('pass', $this->audit->evaluate_php_version('8.3.1')['status']);
        $this->assertSame('warning', $this->audit->evaluate_php_version('8.1.27')['status']);
        $this->assertSame('critical', $this->audit->evaluate_php_version('7.4.33')['status']);
    }

    public function test_memory_limit_bands(): void
    {
        $this->assertSame('pass', $this->audit->evaluate_memory_limit('256M')['status']);
        $this->assertSame('pass', $this->audit->evaluate_memory_limit('128M')['status']);
        $this->assertSame('warning', $this->audit->evaluate_memory_limit('64M')['status']);
        $this->assertSame('pass', $this->audit->evaluate_memory_limit('-1')['status']);
    }

    public function test_opcache_pass_when_enabled_warning_when_disabled(): void
    {
        $this->assertSame('pass', $this->audit->evaluate_opcache(true)['status']);
        $this->assertSame('warning', $this->audit->evaluate_opcache(false)['status']);
    }

    public function test_object_cache_pass_when_persistent_warning_when_not(): void
    {
        $this->assertSame('pass', $this->audit->evaluate_object_cache(true)['status']);
        $this->assertSame('warning', $this->audit->evaluate_object_cache(false)['status']);
    }

    public function test_image_lib_passes_when_either_library_present(): void
    {
        $this->assertSame('pass', $this->audit->evaluate_image_lib(true, false)['status']);
        $this->assertSame('pass', $this->audit->evaluate_image_lib(false, true)['status']);
        $this->assertSame('pass', $this->audit->evaluate_image_lib(true, true)['status']);
        $this->assertSame('warning', $this->audit->evaluate_image_lib(false, false)['status']);
    }
}
