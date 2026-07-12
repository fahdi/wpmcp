<?php

namespace WPMCP\Tests\Free\Users;

use WPMCP\Tools\Users\Update_User;
use WPMCP\Safety\Snapshot_Store;

class UpdateUserTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Snapshot_Store::install();
    }

    public function test_update_user_edits_non_admin_profile_fields(): void
    {
        $id = self::factory()->user->create([
            'role'         => 'author',
            'display_name' => 'Jane Doe',
            'description'  => 'Writer',
        ]);

        $out = (new Update_User())->handle([
            'id'           => $id,
            'display_name' => 'Jane Q. Doe',
            'description'  => 'Senior writer',
        ]);

        $this->assertContains('display_name', $out['updated']);
        $this->assertContains('description', $out['updated']);

        $user = get_userdata($id);
        $this->assertSame('Jane Q. Doe', $user->display_name);
        $this->assertSame('Senior writer', $user->description);
    }

    public function test_update_user_refuses_admin_target(): void
    {
        $id = self::factory()->user->create(['role' => 'administrator', 'description' => 'original']);

        try {
            (new Update_User())->handle(['id' => $id, 'description' => 'hacked']);
            $this->fail('Expected a refusal for an admin target.');
        } catch (\RuntimeException $e) {
            $this->assertSame('original', get_userdata($id)->description, 'Admin profile must be untouched.');
        }
    }

    public function test_update_user_ignores_role_and_password(): void
    {
        $id       = self::factory()->user->create(['role' => 'subscriber']);
        $old_hash = get_userdata($id)->user_pass;

        (new Update_User())->handle([
            'id'           => $id,
            'role'         => 'administrator',
            'password'     => 'newpass',
            'user_pass'    => 'newpass',
            'display_name' => 'Renamed',
        ]);

        $user = get_userdata($id);
        $this->assertContains('subscriber', $user->roles, 'Role must not change.');
        $this->assertNotContains('administrator', $user->roles);
        $this->assertSame($old_hash, $user->user_pass, 'Password hash must not change.');
        $this->assertSame('Renamed', $user->display_name);
    }

    public function test_update_user_not_found_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Update_User())->handle(['id' => 999999, 'display_name' => 'x']);
    }

    public function test_update_user_requires_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Update_User())->handle(['display_name' => 'x']);
    }

    public function test_update_user_is_snapshotted_and_rollback_restores_profile(): void
    {
        $id = self::factory()->user->create([
            'role'         => 'author',
            'display_name' => 'Original Name',
            'description'  => 'Original bio',
        ]);

        $out = (new Update_User())->handle([
            'id'           => $id,
            'display_name' => 'Changed Name',
            'description'  => 'Changed bio',
        ]);

        $this->assertArrayHasKey('operation_id', $out);
        $this->assertSame('Changed Name', get_userdata($id)->display_name);

        $operation_id = $out['operation_id'];
        $this->assertNotNull(Snapshot_Store::get_by_operation($operation_id));

        $rolled_back = (new \WPMCP\Tools\Rollback_Operation())->handle(['operation_id' => $operation_id]);
        $this->assertTrue($rolled_back['restored']);

        $user = get_userdata($id);
        $this->assertSame('Original Name', $user->display_name);
        $this->assertSame('Original bio', $user->description);
    }
}
