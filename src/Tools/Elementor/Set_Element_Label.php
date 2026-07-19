<?php

namespace WPMCP\Tools\Elementor;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Set an element's navigator label (issue #58), which Elementor stores as
 * the `_title` setting. An empty label clears the custom name so the
 * navigator falls back to the element's default title. Every other
 * settings key survives untouched. Hash-guarded and snapshot-first.
 */
class Set_Element_Label
{
    public function handle(array $args)
    {
        $element_id = (string) ($args['element_id'] ?? '');
        if ('' === $element_id) {
            return new \WP_Error('missing_element_id', 'An element_id is required.');
        }

        if (! isset($args['label']) || ! is_string($args['label'])) {
            return new \WP_Error('missing_label', 'A "label" string is required (empty string clears the label).');
        }
        $label = $args['label'];

        $read = Element_Tree::read_for_edit($args);
        if (is_wp_error($read)) {
            return $read;
        }
        [$post_id, $elements] = $read;

        if (null === Elementor_Page_Data::find($elements, $element_id)) {
            return new \WP_Error('element_not_found', "No element found with id '{$element_id}'.");
        }

        $this->apply_label($elements, $element_id, $label);

        $out = Element_Tree::write($post_id, $elements, 'set-element-label', $args);
        if (is_wp_error($out)) {
            return $out;
        }

        return $out + ['element_id' => $element_id, 'label' => $label];
    }

    private function apply_label(array &$elements, string $element_id, string $label): bool
    {
        foreach ($elements as &$item) {
            if (($item['id'] ?? null) === $element_id) {
                if (! isset($item['settings']) || ! is_array($item['settings'])) {
                    $item['settings'] = [];
                }
                if ('' === $label) {
                    unset($item['settings']['_title']);
                } else {
                    $item['settings']['_title'] = $label;
                }
                return true;
            }
            if (! empty($item['elements']) && is_array($item['elements'])) {
                if ($this->apply_label($item['elements'], $element_id, $label)) {
                    return true;
                }
            }
        }

        return false;
    }
}
