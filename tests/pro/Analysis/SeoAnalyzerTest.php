<?php

namespace WPMCP\Tests\Pro\Analysis;

use WPMCP\Pro\Gate;
use WPMCP\Tools\Analysis\Seo_Analyzer;

class SeoAnalyzerTest extends \WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Gate::set_pro_for_tests(true);
    }

    protected function tearDown(): void
    {
        Gate::set_pro_for_tests(null);
        parent::tearDown();
    }

    private function good_extract(): array
    {
        $body = str_repeat('Quality dental care for the whole family in our modern clinic. ', 12);
        return [
            'headings'   => [
                ['level' => 1, 'text' => 'Family Dentist in Springfield'],
                ['level' => 2, 'text' => 'Our Services'],
            ],
            'links'      => [
                ['url' => '/services', 'text' => 'Services', 'internal' => true],
                ['url' => 'https://ada.org', 'text' => 'ADA', 'internal' => false],
            ],
            'images'     => [
                ['src' => 'x.jpg', 'alt' => 'Smiling patient', 'location' => 'img[1]'],
            ],
            'text'       => $body,
            'word_count' => 320,
        ];
    }

    private function good_seo(): array
    {
        return [
            'title'         => 'Family Dentist in Springfield | Bright Smiles Dental',
            'description'   => 'Gentle, affordable family dentistry in Springfield. Same-day emergency visits, Invisalign, and friendly care for every age. Book your visit today.',
            'focus_keyword' => 'family dentist',
        ];
    }

    private function status_map(array $report): array
    {
        return array_column($report['checks'], 'status', 'id');
    }

    public function test_good_page_scores_high_and_passes_core_checks(): void
    {
        $report = Seo_Analyzer::analyze($this->good_extract(), $this->good_seo(), 'family dentist');
        $status = $this->status_map($report);

        $this->assertGreaterThanOrEqual(80, $report['score']);
        $this->assertSame('pass', $status['h1_present']);
        $this->assertSame('pass', $status['meta_description']);
        $this->assertSame('pass', $status['title_length']);
        $this->assertSame('pass', $status['image_alts']);
        $this->assertSame('pass', $status['word_count']);
    }

    public function test_missing_meta_description_fails(): void
    {
        $seo                = $this->good_seo();
        $seo['description'] = '';
        $report             = Seo_Analyzer::analyze($this->good_extract(), $seo, '');
        $status             = $this->status_map($report);

        $this->assertSame('fail', $status['meta_description']);
    }

    public function test_too_short_title_fails(): void
    {
        $seo          = $this->good_seo();
        $seo['title'] = 'Dentist';
        $report       = Seo_Analyzer::analyze($this->good_extract(), $seo, '');
        $status       = $this->status_map($report);

        $this->assertSame('fail', $status['title_length']);
    }

    public function test_missing_h1_and_alts_are_flagged(): void
    {
        $ex             = $this->good_extract();
        $ex['headings'] = [['level' => 2, 'text' => 'No H1 here']];
        $ex['images'][] = ['src' => 'y.jpg', 'alt' => '', 'location' => 'img[2]'];

        $report = Seo_Analyzer::analyze($ex, $this->good_seo(), '');
        $status = $this->status_map($report);

        $this->assertSame('fail', $status['h1_present']);
        $this->assertSame('fail', $status['image_alts']);
    }

    public function test_skipped_heading_level_warns(): void
    {
        $ex             = $this->good_extract();
        $ex['headings'] = [
            ['level' => 1, 'text' => 'Title'],
            ['level' => 3, 'text' => 'Jumped'],
        ];
        $report = Seo_Analyzer::analyze($ex, $this->good_seo(), '');
        $status = $this->status_map($report);

        $this->assertSame('warn', $status['heading_hierarchy']);
    }

    public function test_thin_content_word_count_fails(): void
    {
        $ex               = $this->good_extract();
        $ex['word_count'] = 40;
        $ex['text']       = 'Just a few words here on the page today.';
        $report           = Seo_Analyzer::analyze($ex, $this->good_seo(), '');
        $status           = $this->status_map($report);

        $this->assertSame('fail', $status['word_count']);
    }

    public function test_link_counts_reported(): void
    {
        $report = Seo_Analyzer::analyze($this->good_extract(), $this->good_seo(), '');
        $this->assertSame(1, $report['metrics']['internal_links']);
        $this->assertSame(1, $report['metrics']['external_links']);
    }

    public function test_focus_keyword_density_reported_when_provided(): void
    {
        $report = Seo_Analyzer::analyze($this->good_extract(), $this->good_seo(), 'family');
        $status = $this->status_map($report);

        $this->assertArrayHasKey('keyword_usage', $status);
        $this->assertGreaterThan(0, $report['metrics']['keyword_density']);
    }

    public function test_readability_score_present(): void
    {
        $report = Seo_Analyzer::analyze($this->good_extract(), $this->good_seo(), '');
        $this->assertArrayHasKey('readability', $report['metrics']);
        $this->assertIsFloat($report['metrics']['readability']);
    }

    public function test_flesch_reading_ease_matches_known_range(): void
    {
        // Simple text scores high (easy); complex text scores lower.
        $easy = Seo_Analyzer::flesch_reading_ease('The cat sat on the mat. The dog ran fast.');
        $hard = Seo_Analyzer::flesch_reading_ease(
            'Notwithstanding the aforementioned considerations, the epistemological ramifications remain fundamentally indeterminate.'
        );

        $this->assertGreaterThan(70.0, $easy);
        $this->assertLessThan($easy, $hard);
    }
}
