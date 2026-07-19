<?php

namespace WPMCP\Tests\Pro\Elementor;

use WPMCP\Tools\Elementor\Get_Elementor_Data;

/**
 * Issue #58 extends get-elementor-data to be the read half of the
 * hash-guarded concurrency contract: it reports data_hash (over the raw
 * _elementor_data JSON) and the page settings with their own settings_hash,
 * which the structural mutations require back as expected_hash.
 */
class GetElementorDataHashTest extends Structural_Harness
{
    public function test_reports_data_hash_over_raw_elementor_data(): void
    {
        $post_id = $this->make_page();

        $out = (new Get_Elementor_Data())->handle(['post_id' => $post_id]);

        $this->assertSame($this->data_hash($post_id), $out['data_hash']);
        $this->assertIsArray($out['elements']);
    }

    public function test_reports_page_settings_and_settings_hash(): void
    {
        $post_id = $this->make_page();
        update_post_meta($post_id, '_elementor_page_settings', ['hide_title' => 'yes']);

        $out = (new Get_Elementor_Data())->handle(['post_id' => $post_id]);

        $this->assertSame(['hide_title' => 'yes'], $out['page_settings']);
        $this->assertSame($this->settings_hash($post_id), $out['settings_hash']);
    }

    public function test_reports_empty_settings_and_stable_hashes_for_bare_page(): void
    {
        $post_id = self::factory()->post->create(['post_type' => 'page']);

        $out = (new Get_Elementor_Data())->handle(['post_id' => $post_id]);

        $this->assertSame([], $out['elements']);
        $this->assertSame(hash('sha256', ''), $out['data_hash']);
        $this->assertSame([], $out['page_settings']);
        $this->assertSame(hash('sha256', wp_json_encode([])), $out['settings_hash']);
    }
}
