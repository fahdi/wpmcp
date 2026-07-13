<?php

namespace WPMCP\Tests\Free\Blocks;

use WPMCP\Tools\Blocks\Parse_Blocks;
use WPMCP\Tools\Blocks\Serialize_Blocks;

class SerializeBlocksTest extends \WP_UnitTestCase
{
    public function test_serializes_a_block_tree_to_markup(): void
    {
        $tree = [
            [
                'blockName'   => 'core/paragraph',
                'attrs'       => [],
                'innerBlocks' => [],
                'innerHTML'   => '<p>hi</p>',
            ],
        ];

        $out = (new Serialize_Blocks())->handle(['blocks' => $tree]);

        $this->assertArrayHasKey('markup', $out);
        $this->assertStringContainsString('<!-- wp:paragraph -->', $out['markup']);
        $this->assertStringContainsString('<p>hi</p>', $out['markup']);
    }

    public function test_round_trips_parse_then_serialize_then_parse(): void
    {
        $original = '<!-- wp:paragraph --><p>hi</p><!-- /wp:paragraph -->';

        $parsed = (new Parse_Blocks())->handle(['blocks' => $original]);

        $serialized = (new Serialize_Blocks())->handle(['blocks' => $parsed['blocks']]);

        $reparsed = (new Parse_Blocks())->handle(['blocks' => $serialized['markup']]);

        $this->assertSame(
            $parsed['blocks'][0]['blockName'],
            $reparsed['blocks'][0]['blockName']
        );
    }

    public function test_throws_when_blocks_argument_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Serialize_Blocks())->handle([]);
    }
}
