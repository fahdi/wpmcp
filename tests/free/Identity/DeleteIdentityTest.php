<?php

namespace WPMCP\Tests\Free\Identity;

use WPMCP\Identity\Identity_Store;
use WPMCP\Tools\Identity\Delete_Identity;

class DeleteIdentityTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        delete_option(Identity_Store::OPTION);
    }

    protected function tearDown(): void
    {
        delete_option(Identity_Store::OPTION);
        parent::tearDown();
    }

    public function test_deletes_an_existing_identity(): void
    {
        Identity_Store::create('temp-bot', []);

        $out = (new Delete_Identity())->handle(['name' => 'temp-bot']);

        $this->assertTrue($out['deleted']);
        $this->assertNull(Identity_Store::get('temp-bot'));
    }

    public function test_returns_wp_error_for_an_unknown_identity(): void
    {
        $out = (new Delete_Identity())->handle(['name' => 'does-not-exist']);

        $this->assertInstanceOf(\WP_Error::class, $out);
        $this->assertSame('wpmcp_identity_not_found', $out->get_error_code());
    }

    public function test_throws_when_name_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Delete_Identity())->handle([]);
    }
}
