<?php

namespace WPMCP\Tests\Pro\Elementor;

use WPMCP\Pro\Gate;
use WPMCP\Safety\Snapshot_Store;

/**
 * Shared harness for the Elementor structural editing suite (issue #58).
 *
 * Every structural tool writes through Elementor's own Document::save()
 * path, which requires a current user who may edit the page and an active
 * Elementor kit (the WP test framework deletes all posts before each test,
 * taking the default kit post with it), so this base class provisions both
 * on top of the usual pro gate + snapshot store setup.
 */
abstract class Structural_Harness extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Gate::set_pro_for_tests(true);
        Snapshot_Store::install();

        if (! wpmcp_elementor_active()) {
            $this->markTestSkipped('Elementor not active');
        }

        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

        $kits = \Elementor\Plugin::instance()->kits_manager;
        if (! $kits->get_active_id() || ! get_post((int) $kits->get_active_id())) {
            update_option('elementor_active_kit', \Elementor\Core\Kits\Manager::create_default_kit());
        }
    }

    protected function tearDown(): void
    {
        Gate::set_pro_for_tests(null);
        parent::tearDown();
    }

    /**
     * Two top-level containers: the first holds a heading + text-editor,
     * the second a button. Shaped like Elementor's own canonical export
     * (widgetType last on widgets, isInner on non-widgets).
     */
    protected function default_tree(): array
    {
        return [
            [
                'id'       => 'cont001',
                'elType'   => 'container',
                'settings' => ['flex_direction' => 'column', 'css_classes' => 'hero primary'],
                'elements' => [
                    [
                        'id'         => 'wid0001',
                        'elType'     => 'widget',
                        'settings'   => ['title' => 'Hello', '_css_classes' => 'headline'],
                        'elements'   => [],
                        'widgetType' => 'heading',
                    ],
                    [
                        'id'         => 'wid0002',
                        'elType'     => 'widget',
                        'settings'   => ['editor' => '<p>Body</p>'],
                        'elements'   => [],
                        'widgetType' => 'text-editor',
                    ],
                ],
                'isInner'  => false,
            ],
            [
                'id'       => 'cont002',
                'elType'   => 'container',
                'settings' => ['flex_direction' => 'row'],
                'elements' => [
                    [
                        'id'         => 'wid0003',
                        'elType'     => 'widget',
                        'settings'   => ['text' => 'Go'],
                        'elements'   => [],
                        'widgetType' => 'button',
                    ],
                ],
                'isInner'  => false,
            ],
        ];
    }

    protected function make_page(?array $tree = null): int
    {
        $post_id = self::factory()->post->create(['post_type' => 'page']);
        update_post_meta($post_id, '_elementor_data', wp_json_encode($tree ?? $this->default_tree()));
        update_post_meta($post_id, '_elementor_edit_mode', 'builder');
        return $post_id;
    }

    protected function raw(int $post_id): string
    {
        $raw = get_post_meta($post_id, '_elementor_data', true);
        return is_string($raw) ? $raw : '';
    }

    protected function data_hash(int $post_id): string
    {
        return hash('sha256', $this->raw($post_id));
    }

    protected function tree(int $post_id): array
    {
        $decoded = json_decode($this->raw($post_id), true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function settings_hash(int $post_id): string
    {
        $settings = get_post_meta($post_id, '_elementor_page_settings', true);
        return hash('sha256', wp_json_encode(is_array($settings) ? $settings : []));
    }

    protected function find_in(array $elements, string $id): ?array
    {
        foreach ($elements as $element) {
            if (($element['id'] ?? null) === $id) {
                return $element;
            }
            if (! empty($element['elements']) && is_array($element['elements'])) {
                $found = $this->find_in($element['elements'], $id);
                if (null !== $found) {
                    return $found;
                }
            }
        }
        return null;
    }

    /** Every element id in the tree, in document order. */
    protected function all_ids(array $elements): array
    {
        $ids = [];
        foreach ($elements as $element) {
            $ids[] = $element['id'] ?? '';
            if (! empty($element['elements']) && is_array($element['elements'])) {
                $ids = array_merge($ids, $this->all_ids($element['elements']));
            }
        }
        return $ids;
    }

    /**
     * "The builder opens the page without warnings" proxy: every stored
     * element must be instantiable by Elementor's own elements manager
     * (unknown types are what the editor warns about and then drops), and
     * every id must be unique and in Elementor's 7-char format.
     */
    protected function assert_builder_clean(int $post_id): void
    {
        $tree = $this->tree($post_id);
        $ids  = $this->all_ids($tree);
        $this->assertSame(count($ids), count(array_unique($ids)), 'Element ids must be unique across the page.');
        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{7}$/', $id, 'Element id must be 7-char hex.');
        }
        $this->assert_elements_instantiable($tree);
    }

    private function assert_elements_instantiable(array $elements): void
    {
        foreach ($elements as $element) {
            $instance = \Elementor\Plugin::instance()->elements_manager->create_element_instance($element);
            $this->assertNotNull(
                $instance,
                sprintf('Element %s (%s) must be instantiable by Elementor.', $element['id'] ?? '?', $element['elType'] ?? '?')
            );
            if (! empty($element['elements']) && is_array($element['elements'])) {
                $this->assert_elements_instantiable($element['elements']);
            }
        }
    }
}
