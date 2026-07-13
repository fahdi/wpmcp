<?php

namespace WPMCP\Tests\Pro\Elementor;

use WPMCP\Tools\Elementor\Widget_Schema;

/**
 * Widget_Schema is the data-driven catalog of widget types generate-widget
 * supports: for each type, which input keys are required, and how to build
 * Elementor's real settings array from validated input. Pure logic, no
 * WordPress or Elementor runtime needed, so this runs as a plain unit test.
 */
class WidgetSchemaTest extends \WP_UnitTestCase
{
    public function test_supports_returns_true_for_known_types(): void
    {
        $this->assertTrue(Widget_Schema::supports('heading'));
        $this->assertTrue(Widget_Schema::supports('text-editor'));
        $this->assertTrue(Widget_Schema::supports('button'));
        $this->assertTrue(Widget_Schema::supports('image'));
    }

    public function test_supports_returns_false_for_unknown_type(): void
    {
        $this->assertFalse(Widget_Schema::supports('totally-fake-widget'));
    }

    public function test_required_keys_for_heading(): void
    {
        $this->assertSame(['title'], Widget_Schema::required_keys('heading'));
    }

    public function test_required_keys_for_text_editor(): void
    {
        $this->assertSame(['editor'], Widget_Schema::required_keys('text-editor'));
    }

    public function test_required_keys_for_button(): void
    {
        $this->assertSame(['text'], Widget_Schema::required_keys('button'));
    }

    public function test_required_keys_for_image(): void
    {
        $this->assertSame(['url'], Widget_Schema::required_keys('image'));
    }

    public function test_missing_required_keys_reports_the_missing_key(): void
    {
        $this->assertSame(['title'], Widget_Schema::missing_required_keys('heading', []));
        $this->assertSame([], Widget_Schema::missing_required_keys('heading', ['title' => 'Hi']));
    }

    public function test_build_settings_for_heading_applies_defaults(): void
    {
        $settings = Widget_Schema::build_settings('heading', ['title' => 'Hello World']);

        $this->assertSame('Hello World', $settings['title']);
        $this->assertSame('h2', $settings['header_size']);
        $this->assertSame('left', $settings['align']);
    }

    public function test_build_settings_for_heading_honors_overrides(): void
    {
        $settings = Widget_Schema::build_settings('heading', [
            'title'       => 'Hello',
            'header_size' => 'h1',
            'align'       => 'center',
        ]);

        $this->assertSame('h1', $settings['header_size']);
        $this->assertSame('center', $settings['align']);
    }

    public function test_build_settings_for_text_editor(): void
    {
        $settings = Widget_Schema::build_settings('text-editor', ['editor' => '<p>Body copy</p>']);

        $this->assertSame('<p>Body copy</p>', $settings['editor']);
    }

    public function test_build_settings_for_button_applies_defaults(): void
    {
        $settings = Widget_Schema::build_settings('button', ['text' => 'Click me']);

        $this->assertSame('Click me', $settings['text']);
        $this->assertSame('', $settings['link']['url']);
        $this->assertSame('center', $settings['align']);
    }

    public function test_build_settings_for_button_honors_link_url_override(): void
    {
        $settings = Widget_Schema::build_settings('button', [
            'text' => 'Go',
            'link' => ['url' => 'https://example.com'],
        ]);

        $this->assertSame('https://example.com', $settings['link']['url']);
    }

    public function test_build_settings_for_image_applies_defaults(): void
    {
        $settings = Widget_Schema::build_settings('image', ['url' => 'https://example.com/photo.jpg']);

        $this->assertSame('https://example.com/photo.jpg', $settings['image']['url']);
        $this->assertSame(0, $settings['image']['id']);
        $this->assertSame('center', $settings['align']);
    }

    public function test_build_settings_for_image_honors_id_override(): void
    {
        $settings = Widget_Schema::build_settings('image', [
            'url' => 'https://example.com/photo.jpg',
            'id'  => 42,
        ]);

        $this->assertSame(42, $settings['image']['id']);
    }
}
