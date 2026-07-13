<?php

namespace WPMCP\Tests\Free\Identity;

use WPMCP\Identity\Identity_Store;
use WPMCP\Tools\Identity\Create_Identity;

class CreateIdentityTest extends \WP_UnitTestCase
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

    public function test_creates_an_identity_with_the_given_scope(): void
    {
        $out = (new Create_Identity())->handle([
            'name'    => 'content-only-bot',
            'domains' => ['content'],
        ]);

        $this->assertSame('content-only-bot', $out['name']);
        $this->assertSame(['content'], $out['domains']);
        $this->assertNotNull(Identity_Store::get('content-only-bot'));
    }

    public function test_throws_when_name_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Create_Identity())->handle(['domains' => ['content']]);
    }

    public function test_throws_when_name_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Create_Identity())->handle(['name' => '']);
    }
}
