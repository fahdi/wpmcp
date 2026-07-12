<?php

namespace WPMCP\Tests\Free\Users;

use WPMCP\Tools\Users\Create_User;

class CreateUserTest extends \WP_UnitTestCase
{
    public function test_create_user_makes_subscriber_by_default_and_returns_no_password(): void
    {
        $out = (new Create_User())->handle([
            'username' => 'newbie',
            'email'    => 'newbie@example.com',
        ]);

        $this->assertGreaterThan(0, $out['id']);
        $this->assertSame('subscriber', $out['role']);
        $this->assertArrayNotHasKey('password', $out);
        $this->assertArrayNotHasKey('generated_password', $out);
        $this->assertArrayNotHasKey('user_pass', $out);

        $user = get_userdata($out['id']);
        $this->assertContains('subscriber', $user->roles);
        $this->assertSame('newbie@example.com', $user->user_email);
    }

    public function test_create_user_generates_a_strong_password_and_emails_the_user(): void
    {
        reset_phpmailer_instance();

        $out  = (new Create_User())->handle(['username' => 'mailed', 'email' => 'mailed@example.com']);
        $user = get_userdata($out['id']);

        // A real (non-empty) hash was stored, i.e. a password was generated.
        $this->assertNotEmpty($user->user_pass);

        // The new-user notification email was sent.
        $mailer = tests_retrieve_phpmailer_instance();
        $this->assertNotEmpty($mailer->mock_sent);
        $recipients = array_column(array_column($mailer->mock_sent, 'to'), 0);
        $recipients = array_column($recipients, 0);
        $this->assertContains('mailed@example.com', $recipients);
    }

    public function test_create_user_rejects_admin_role(): void
    {
        $before = count_users()['total_users'];
        try {
            (new Create_User())->handle([
                'username' => 'evil',
                'email'    => 'evil@example.com',
                'role'     => 'administrator',
            ]);
            $this->fail('Expected an exception for an admin role.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame($before, count_users()['total_users'], 'No user should have been created.');
        }
    }

    public function test_create_user_rejects_unknown_role(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Create_User())->handle([
            'username' => 'wiz',
            'email'    => 'wiz@example.com',
            'role'     => 'wizard',
        ]);
    }

    public function test_create_user_requires_username_and_email(): void
    {
        $threw = 0;
        foreach ([['email' => 'a@b.com'], ['username' => 'a']] as $args) {
            try {
                (new Create_User())->handle($args);
            } catch (\InvalidArgumentException $e) {
                $threw++;
            }
        }
        $this->assertSame(2, $threw);
    }

    public function test_create_user_surfaces_insert_error_for_duplicate(): void
    {
        self::factory()->user->create(['user_login' => 'dup', 'user_email' => 'dup@example.com']);

        $this->expectException(\RuntimeException::class);
        (new Create_User())->handle(['username' => 'dup', 'email' => 'dup2@example.com']);
    }
}
