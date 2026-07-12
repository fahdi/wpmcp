<?php

namespace WPMCP\Tests\Free\Security;

use WPMCP\Tools\Security\Security_Finding;
use WPMCP\Tools\Security\Security_Scanner;

class SecurityScannerTest extends \WP_UnitTestCase
{
    private function scanner(): Security_Scanner
    {
        return new Security_Scanner();
    }

    private function finding(string $category, string $status, string $rec = 'fix it', string $label = 'L'): array
    {
        return Security_Finding::make('id', $category, $label, $status, null, 'm', $rec);
    }

    public function test_resolve_checks_defaults_to_all_four_in_canonical_order(): void
    {
        $this->assertSame(['malware', 'integrity', 'hardening', 'software'], $this->scanner()->resolve_checks(null));
        $this->assertSame(['malware', 'integrity', 'hardening', 'software'], $this->scanner()->resolve_checks([]));
    }

    public function test_resolve_checks_filters_to_valid_subset_in_canonical_order(): void
    {
        $this->assertSame(
            ['integrity', 'hardening'],
            $this->scanner()->resolve_checks(['hardening', 'integrity', 'bogus'])
        );
    }

    public function test_resolve_checks_falls_back_to_all_when_no_valid_values(): void
    {
        $this->assertSame(
            ['malware', 'integrity', 'hardening', 'software'],
            $this->scanner()->resolve_checks(['bogus', 'nope'])
        );
    }

    public function test_score_subtracts_weighted_penalties_and_bands_grade(): void
    {
        $findings = [
            $this->finding('hardening', 'critical'), // -20
            $this->finding('software', 'warning'),   // -5
            $this->finding('integrity', 'pass'),     // 0
        ];

        $summary = $this->scanner()->summarize($findings);

        $this->assertSame(75, $summary['score']);
        $this->assertSame('C', $summary['grade']);
        $this->assertSame(1, $summary['counts']['critical']);
        $this->assertSame(1, $summary['counts']['warning']);
        $this->assertSame(1, $summary['counts']['pass']);
    }

    public function test_per_category_critical_penalty_is_capped(): void
    {
        // Five malware criticals would be -100, but the per-category cap is -60.
        $findings = array_fill(0, 5, $this->finding('malware', 'critical'));

        $this->assertSame(40, $this->scanner()->summarize($findings)['score']); // 100 - 60
    }

    public function test_score_clamps_at_zero(): void
    {
        $findings = array_merge(
            array_fill(0, 5, $this->finding('malware', 'critical')),   // -60 (capped)
            array_fill(0, 5, $this->finding('integrity', 'critical')), // -60 (capped)
            array_fill(0, 5, $this->finding('hardening', 'critical'))  // -60 (capped) => -180 total
        );

        $this->assertSame(0, $this->scanner()->summarize($findings)['score']);
    }

    public function test_grade_bands_cover_a_through_f(): void
    {
        // 100 -> A.
        $this->assertSame('A', $this->scanner()->summarize([])['grade']);
        // 85 (three -5 warnings) -> B.
        $this->assertSame('B', $this->scanner()->summarize(array_fill(0, 3, $this->finding('software', 'warning')))['grade']);
        // 40 (one capped -60 malware category) -> F.
        $this->assertSame('F', $this->scanner()->summarize(array_fill(0, 5, $this->finding('malware', 'critical')))['grade']);
    }

    public function test_top_recommendations_rank_critical_before_warning(): void
    {
        $findings = [
            $this->finding('software', 'warning', 'warn-rec', 'W'),
            $this->finding('malware', 'critical', 'crit-rec', 'C'),
        ];

        $recs = $this->scanner()->summarize($findings)['top_recommendations'];

        $this->assertStringContainsString('crit-rec', $recs[0]);
        $this->assertStringContainsString('warn-rec', $recs[1]);
    }

    public function test_group_by_category_buckets_findings(): void
    {
        $sections = $this->scanner()->group_by_category([
            $this->finding('malware', 'critical'),
            $this->finding('hardening', 'warning'),
        ]);

        $this->assertCount(1, $sections['malware']);
        $this->assertCount(1, $sections['hardening']);
        $this->assertSame([], $sections['integrity']);
        $this->assertSame([], $sections['software']);
    }
}
