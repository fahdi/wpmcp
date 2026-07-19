<?php

namespace WPMCP\Tools\Elementor;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Deep-copy an element (and its whole subtree) with recursively
 * regenerated ids, inserted immediately after the original among its
 * siblings (issue #58). Fresh ids are generated in Elementor's own 7-char
 * hex format and checked against every id already on the page, so the
 * builder opens the result without duplicate-id warnings. Hash-guarded and
 * snapshot-first.
 */
class Duplicate_Element
{
    public function handle(array $args)
    {
        $element_id = (string) ($args['element_id'] ?? '');
        if ('' === $element_id) {
            return new \WP_Error('missing_element_id', 'An element_id is required.');
        }

        $read = Element_Tree::read_for_edit($args);
        if (is_wp_error($read)) {
            return $read;
        }
        [$post_id, $elements] = $read;

        $element = Elementor_Page_Data::find($elements, $element_id);
        if (null === $element) {
            return new \WP_Error('element_not_found', "No element found with id '{$element_id}'.");
        }

        $taken = [];
        foreach ($this->all_ids($elements) as $id) {
            $taken[ $id ] = true;
        }

        $copy = $this->with_fresh_ids($element, $taken);

        Element_Tree::insert_after($elements, $element_id, $copy);

        $out = Element_Tree::write($post_id, $elements, 'duplicate-element', $args);
        if (is_wp_error($out)) {
            return $out;
        }

        return $out + ['element_id' => $element_id, 'new_element_id' => $copy['id']];
    }

    /** @param array<string,bool> $taken ids already present anywhere on the page (mutated as ids are claimed). */
    private function with_fresh_ids(array $element, array &$taken): array
    {
        do {
            $id = Element_Id::generate();
        } while (isset($taken[ $id ]));
        $taken[ $id ] = true;

        $element['id'] = $id;

        if (! empty($element['elements']) && is_array($element['elements'])) {
            $element['elements'] = array_map(
                fn (array $child) => $this->with_fresh_ids($child, $taken),
                $element['elements']
            );
        }

        return $element;
    }

    private function all_ids(array $elements): array
    {
        $ids = [];
        foreach ($elements as $element) {
            $ids[] = (string) ($element['id'] ?? '');
            if (! empty($element['elements']) && is_array($element['elements'])) {
                $ids = array_merge($ids, $this->all_ids($element['elements']));
            }
        }

        return $ids;
    }
}
