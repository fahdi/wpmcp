<?php

namespace WPMCP\Tests\Free\Identity;

use WPMCP\Identity\Identity_Store;
use WPMCP\Tools\Identity\List_Identities;

class ListIdentitiesTest extends \WP_UnitTestCase
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

    public function test_returns_an_empty_list_with_no_identities(): void
    {
        $out = (new List_Identities())->handle([]);

        $this->assertSame(['identities' => []], $out);
    }

    public function test_returns_all_created_identities(): void
    {
        Identity_Store::create('bot-a', ['domains' => ['content']]);
        Identity_Store::create('bot-b', []);

        $out   = (new List_Identities())->handle([]);
        $names = array_column($out['identities'], 'name');

        $this->assertContains('bot-a', $names);
        $this->assertContains('bot-b', $names);
    }
}
