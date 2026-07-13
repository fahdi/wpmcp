<?php

namespace WPMCP\Tests\Free\Input;

use WPMCP\Tools\Meta\Set_Post_Meta;
use WPMCP\Tools\Meta\Get_Post_Meta;
use WPMCP\Tools\Meta\Update_Option;
use WPMCP\Tools\Meta\Get_Option;

/**
 * Input-boundary tests for the Meta domain: missing/invalid post ids,
 * protected meta keys, denylisted/sensitive option names, and disabled
 * write gates must all fail cleanly (InvalidArgumentException/
 * RuntimeException), never a fatal or a leaked secret.
 */
class MetaInputTest extends \WP_UnitTestCase
{
    public function test_set_post_meta_rejects_missing_post_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Set_Post_Meta())->handle(['key' => 'k', 'value' => 'v']);
    }

    public function test_set_post_meta_rejects_nonexistent_post_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Set_Post_Meta())->handle(['post_id' => 999999999, 'key' => 'k', 'value' => 'v']);
    }

    public function test_set_post_meta_rejects_missing_key(): void
    {
        $id = self::factory()->post->create();
        $this->expectException(\InvalidArgumentException::class);
        (new Set_Post_Meta())->handle(['post_id' => $id, 'value' => 'v']);
    }

    public function test_set_post_meta_rejects_empty_string_key(): void
    {
        $id = self::factory()->post->create();
        $this->expectException(\InvalidArgumentException::class);
        (new Set_Post_Meta())->handle(['post_id' => $id, 'key' => '', 'value' => 'v']);
    }

    public function test_set_post_meta_rejects_protected_underscore_key(): void
    {
        $id = self::factory()->post->create();
        $this->expectException(\InvalidArgumentException::class);
        (new Set_Post_Meta())->handle(['post_id' => $id, 'key' => '_protected_key', 'value' => 'v']);
    }

    public function test_get_post_meta_rejects_missing_post_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Post_Meta())->handle([]);
    }

    public function test_get_post_meta_rejects_nonexistent_post_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Post_Meta())->handle(['post_id' => 999999999]);
    }

    public function test_update_option_rejects_missing_name(): void
    {
        add_filter('wpmcp_enable_option_write', '__return_true');
        $this->expectException(\InvalidArgumentException::class);
        (new Update_Option())->handle(['value' => 'x']);
    }

    public function test_update_option_rejects_denylisted_name(): void
    {
        add_filter('wpmcp_enable_option_write', '__return_true');
        $this->expectException(\RuntimeException::class);
        (new Update_Option())->handle(['name' => 'siteurl', 'value' => 'http://evil.example']);
    }

    public function test_update_option_rejects_name_matching_a_sensitive_pattern(): void
    {
        add_filter('wpmcp_enable_option_write', '__return_true');
        $this->expectException(\RuntimeException::class);
        (new Update_Option())->handle(['name' => 'my_custom_api_key', 'value' => 'x']);
    }

    public function test_update_option_disabled_by_default(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Update_Option())->handle(['name' => 'wpmcp_test_option', 'value' => 'x']);
    }

    public function test_get_option_rejects_missing_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Option())->handle([]);
    }

    public function test_get_option_rejects_denylisted_name(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Get_Option())->handle(['name' => 'auth_key']);
    }
}
