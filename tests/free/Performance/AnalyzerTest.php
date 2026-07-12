<?php

namespace WPMCP\Tests\Free\Performance;

use WPMCP\Tools\Performance\Analyzer;
use WPMCP\Tools\Performance\Finding;

class AnalyzerTest extends \WP_UnitTestCase
{
    private Analyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new Analyzer();
    }

    private function finding(string $status, string $recommendation = ''): array
    {
        return Finding::make('x', 'server', 'X', $status, 1, 'm', $recommendation);
    }

    public function test_summarize_counts_and_scores(): void
    {
        $findings = [
            $this->finding('critical', 'fix me'),
            $this->finding('warning', 'maybe'),
            $this->finding('warning', 'maybe2'),
            $this->finding('pass'),
            $this->finding('info'),
        ];

        $summary = $this->analyzer->summarize($findings);

        $this->assertSame(1, $summary['counts']['critical']);
        $this->assertSame(2, $summary['counts']['warning']);
        $this->assertSame(1, $summary['counts']['pass']);
        $this->assertSame(1, $summary['counts']['info']);
        // 100 - (1 * 15) - (2 * 4) = 77, grade C.
        $this->assertSame(77, $summary['score']);
        $this->assertSame('C', $summary['grade']);
    }
}
