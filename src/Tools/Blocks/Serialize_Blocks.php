<?php

namespace WPMCP\Tools\Blocks;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Pure transform, not a DB write: given a block tree (as produced by
 * Parse_Blocks, or any array shaped the same way), return valid block markup
 * via serialize_blocks(). No Safe_Mutation wrapping, this never touches the
 * database; to write the resulting markup to a post use the existing
 * update-blocks tool.
 */
class Serialize_Blocks
{
    public function handle(array $args): array
    {
        if (! isset($args['blocks']) || ! is_array($args['blocks'])) {
            throw new \InvalidArgumentException('"blocks" (an array of parsed block nodes) is required.');
        }

        $blocks = array_map([$this, 'denormalize'], $args['blocks']);

        return ['markup' => serialize_blocks($blocks)];
    }

    /**
     * Reshape one of our normalized nodes (blockName, attrs, innerBlocks,
     * innerHTML, innerContent) back into the exact keys serialize_block()
     * expects. When innerContent is missing, e.g. a caller hand-built a leaf
     * node, fall back to treating the whole innerHTML as a single fragment,
     * which is correct for any block with no nested inner blocks.
     */
    private function denormalize(array $block): array
    {
        $inner_blocks = array_map([$this, 'denormalize'], $block['innerBlocks'] ?? []);

        $inner_content = $block['innerContent'] ?? null;
        if (! is_array($inner_content)) {
            $inner_content = [] === $inner_blocks ? [(string) ($block['innerHTML'] ?? '')] : [];
        }

        return [
            'blockName'    => $block['blockName'] ?? null,
            'attrs'        => is_array($block['attrs'] ?? null) ? $block['attrs'] : [],
            'innerBlocks'  => $inner_blocks,
            'innerHTML'    => (string) ($block['innerHTML'] ?? ''),
            'innerContent' => $inner_content,
        ];
    }
}
