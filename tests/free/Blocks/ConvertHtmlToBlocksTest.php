<?php

namespace WPMCP\Tests\Free\Blocks;

use WPMCP\Tools\Blocks\Convert_Html_To_Blocks;

class ConvertHtmlToBlocksTest extends \WP_UnitTestCase
{
    public function test_converts_heading_and_paragraph_to_core_blocks(): void
    {
        $html = '<h2>Title</h2><p>Hello</p>';

        $out = (new Convert_Html_To_Blocks())->handle(['html' => $html]);

        $this->assertArrayHasKey('markup', $out);

        $blocks = array_values(array_filter(
            parse_blocks($out['markup']),
            static fn (array $block): bool => null !== $block['blockName']
        ));

        $this->assertCount(2, $blocks);
        $this->assertSame('core/heading', $blocks[0]['blockName']);
        $this->assertSame(2, $blocks[0]['attrs']['level']);
        $this->assertSame('core/paragraph', $blocks[1]['blockName']);
    }
}
