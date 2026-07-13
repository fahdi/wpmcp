<?php

namespace WPMCP\Tools\Analysis;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Pure on-page SEO scoring. Given an extracted-content array (from
 * Content_Extractor), a neutral SEO meta array (title, description,
 * focus_keyword), and an optional focus keyword, it returns a 0-100 score, a
 * list of severity-tagged checks (pass/warn/fail), and a metrics block
 * (link counts, keyword density, Flesch reading ease).
 *
 * No WordPress dependency: everything operates on the passed-in arrays, so the
 * scoring is deterministic and unit-testable in isolation.
 */
class Seo_Analyzer
{
    private const TITLE_MIN       = 30;
    private const TITLE_MAX       = 60;
    private const DESC_MIN        = 50;
    private const DESC_MAX        = 160;
    private const WORD_COUNT_GOOD = 300;
    private const WORD_COUNT_MIN  = 150;

    /**
     * @param array $extract Output of Content_Extractor::extract().
     * @param array $seo     Neutral SEO meta: title, description, focus_keyword.
     * @param string $focus_keyword Optional override for keyword-density scoring.
     */
    public static function analyze(array $extract, array $seo, string $focus_keyword = ''): array
    {
        $headings = $extract['headings'] ?? [];
        $links    = $extract['links'] ?? [];
        $images   = $extract['images'] ?? [];
        $text     = (string) ($extract['text'] ?? '');
        $words    = (int) ($extract['word_count'] ?? 0);

        $title       = (string) ($seo['title'] ?? '');
        $description = (string) ($seo['description'] ?? '');
        $keyword     = '' !== $focus_keyword
            ? $focus_keyword
            : (string) ($seo['focus_keyword'] ?? '');

        $checks = [];

        $checks[] = self::check_title_length($title);
        $checks[] = self::check_meta_description($description);
        $checks[] = self::check_h1($headings);
        $checks[] = self::check_heading_hierarchy($headings);
        $checks[] = self::check_word_count($words);
        $checks[] = self::check_image_alts($images);
        $checks[] = self::check_internal_links($links);

        $density = self::keyword_density($text, $keyword);
        if ('' !== $keyword) {
            $checks[] = self::check_keyword_usage($density);
        }

        $internal = 0;
        $external = 0;
        foreach ($links as $link) {
            if (! empty($link['internal'])) {
                $internal++;
            } else {
                $external++;
            }
        }

        return [
            'score'   => self::score($checks),
            'checks'  => $checks,
            'metrics' => [
                'word_count'      => $words,
                'internal_links'  => $internal,
                'external_links'  => $external,
                'keyword_density' => $density,
                'readability'     => self::flesch_reading_ease($text),
                'title_length'    => mb_strlen($title),
                'description_length' => mb_strlen($description),
            ],
            'summary' => self::summarize($checks),
        ];
    }

    private static function check(string $id, string $label, string $status, string $message, string $recommendation = ''): array
    {
        return [
            'id'             => $id,
            'label'          => $label,
            'status'         => $status,
            'message'        => $message,
            'recommendation' => $recommendation,
        ];
    }

    private static function check_title_length(string $title): array
    {
        $len = mb_strlen($title);
        if ($len < self::TITLE_MIN) {
            return self::check(
                'title_length',
                'Title length',
                'fail',
                sprintf('The SEO title is %d characters, shorter than the recommended %d.', $len, self::TITLE_MIN),
                'Write a descriptive title of 30-60 characters that includes the primary topic.'
            );
        }
        if ($len > self::TITLE_MAX) {
            return self::check(
                'title_length',
                'Title length',
                'warn',
                sprintf('The SEO title is %d characters, longer than the recommended %d.', $len, self::TITLE_MAX),
                'Trim the title to 60 characters or fewer so it is not truncated in search results.'
            );
        }
        return self::check('title_length', 'Title length', 'pass', sprintf('The SEO title is %d characters.', $len));
    }

    private static function check_meta_description(string $description): array
    {
        $len = mb_strlen($description);
        if (0 === $len) {
            return self::check(
                'meta_description',
                'Meta description',
                'fail',
                'No meta description is set.',
                'Add a 50-160 character meta description summarizing the page.'
            );
        }
        if ($len < self::DESC_MIN || $len > self::DESC_MAX) {
            return self::check(
                'meta_description',
                'Meta description',
                'warn',
                sprintf('The meta description is %d characters, outside the recommended %d-%d range.', $len, self::DESC_MIN, self::DESC_MAX),
                'Aim for a meta description between 50 and 160 characters.'
            );
        }
        return self::check('meta_description', 'Meta description', 'pass', sprintf('The meta description is %d characters.', $len));
    }

    private static function check_h1(array $headings): array
    {
        $h1_count = 0;
        foreach ($headings as $h) {
            if (1 === (int) ($h['level'] ?? 0)) {
                $h1_count++;
            }
        }
        if (0 === $h1_count) {
            return self::check(
                'h1_present',
                'H1 heading',
                'fail',
                'The page has no H1 heading.',
                'Add exactly one H1 that states the page topic.'
            );
        }
        if ($h1_count > 1) {
            return self::check(
                'h1_present',
                'H1 heading',
                'warn',
                sprintf('The page has %d H1 headings.', $h1_count),
                'Use a single H1 per page and demote the rest to H2/H3.'
            );
        }
        return self::check('h1_present', 'H1 heading', 'pass', 'The page has exactly one H1.');
    }

    private static function check_heading_hierarchy(array $headings): array
    {
        $previous = 0;
        foreach ($headings as $h) {
            $level = (int) ($h['level'] ?? 0);
            if ($previous > 0 && $level > $previous + 1) {
                return self::check(
                    'heading_hierarchy',
                    'Heading hierarchy',
                    'warn',
                    sprintf('Heading levels jump from H%d to H%d, skipping a level.', $previous, $level),
                    'Do not skip heading levels; step down one level at a time (H2 then H3, not H2 then H4).'
                );
            }
            $previous = $level;
        }
        return self::check('heading_hierarchy', 'Heading hierarchy', 'pass', 'Heading levels descend without skips.');
    }

    private static function check_word_count(int $words): array
    {
        if ($words < self::WORD_COUNT_MIN) {
            return self::check(
                'word_count',
                'Content length',
                'fail',
                sprintf('The page has %d words, below the %d-word threshold for substantive content.', $words, self::WORD_COUNT_MIN),
                'Expand the content to at least 300 words of useful copy.'
            );
        }
        if ($words < self::WORD_COUNT_GOOD) {
            return self::check(
                'word_count',
                'Content length',
                'warn',
                sprintf('The page has %d words; aim for 300+ for substantive content.', $words),
                'Consider expanding toward 300+ words where it adds value.'
            );
        }
        return self::check('word_count', 'Content length', 'pass', sprintf('The page has %d words.', $words));
    }

    private static function check_image_alts(array $images): array
    {
        $missing = 0;
        foreach ($images as $img) {
            if ('' === trim((string) ($img['alt'] ?? ''))) {
                $missing++;
            }
        }
        if ($missing > 0) {
            return self::check(
                'image_alts',
                'Image alt text',
                'fail',
                sprintf('%d of %d image(s) are missing alt text.', $missing, count($images)),
                'Add descriptive alt text to every content image.'
            );
        }
        return self::check('image_alts', 'Image alt text', 'pass', 'All images have alt text (or there are none).');
    }

    private static function check_internal_links(array $links): array
    {
        $internal = 0;
        foreach ($links as $link) {
            if (! empty($link['internal'])) {
                $internal++;
            }
        }
        if (0 === $internal) {
            return self::check(
                'internal_links',
                'Internal links',
                'warn',
                'The page has no internal links.',
                'Link to related pages on your site to spread authority and aid navigation.'
            );
        }
        return self::check('internal_links', 'Internal links', 'pass', sprintf('The page has %d internal link(s).', $internal));
    }

    private static function check_keyword_usage(float $density): array
    {
        if ($density <= 0.0) {
            return self::check(
                'keyword_usage',
                'Focus keyword usage',
                'fail',
                'The focus keyword does not appear in the content.',
                'Use the focus keyword naturally in the copy, ideally in the first paragraph and a heading.'
            );
        }
        if ($density > 3.5) {
            return self::check(
                'keyword_usage',
                'Focus keyword usage',
                'warn',
                sprintf('The focus keyword density is %.1f%%, which can read as keyword stuffing.', $density),
                'Reduce repetition; aim for roughly 0.5-3% density.'
            );
        }
        return self::check('keyword_usage', 'Focus keyword usage', 'pass', sprintf('The focus keyword density is %.1f%%.', $density));
    }

    private static function keyword_density(string $text, string $keyword): float
    {
        $keyword = trim(strtolower($keyword));
        if ('' === $keyword) {
            return 0.0;
        }
        $haystack = ' ' . strtolower(preg_replace('/\s+/', ' ', $text)) . ' ';
        $total    = count(preg_split('/\s+/', trim(strtolower($text))) ?: []);
        if (0 === $total) {
            return 0.0;
        }
        $matches   = substr_count($haystack, ' ' . $keyword . ' ');
        $key_words = max(1, count(preg_split('/\s+/', $keyword) ?: []));
        return round(($matches * $key_words / $total) * 100, 2);
    }

    /**
     * Flesch Reading Ease: 206.835 - 1.015*(words/sentences) - 84.6*(syllables/words).
     * Higher is easier (0-100 typical range, can exceed). Empty text returns 0.
     */
    public static function flesch_reading_ease(string $text): float
    {
        $text = trim($text);
        if ('' === $text) {
            return 0.0;
        }

        $sentences = max(1, preg_match_all('/[.!?]+(?:\s|$)/', $text));
        $words     = preg_split('/\s+/', $text) ?: [];
        $words     = array_values(array_filter($words, static fn($w) => '' !== trim($w)));
        $word_total = count($words);
        if (0 === $word_total) {
            return 0.0;
        }

        $syllables = 0;
        foreach ($words as $word) {
            $syllables += self::count_syllables($word);
        }

        $score = 206.835
            - 1.015 * ($word_total / $sentences)
            - 84.6 * ($syllables / $word_total);

        return round($score, 1);
    }

    /**
     * Heuristic English syllable counter: count vowel groups, drop a trailing
     * silent "e", and floor at one syllable per word.
     */
    private static function count_syllables(string $word): int
    {
        $word = strtolower(preg_replace('/[^a-z]/i', '', $word));
        if ('' === $word) {
            return 0;
        }
        $word = preg_replace('/e$/', '', $word);
        if ('' === $word) {
            return 1;
        }
        $groups = preg_match_all('/[aeiouy]+/', $word);
        return max(1, (int) $groups);
    }

    /**
     * Roll checks up to a 0-100 score. Each pass is worth full weight, warn
     * half, fail zero; the score is the weighted average as a percentage.
     */
    private static function score(array $checks): int
    {
        if ([] === $checks) {
            return 0;
        }
        $earned = 0.0;
        foreach ($checks as $c) {
            $earned += match ($c['status']) {
                'pass'  => 1.0,
                'warn'  => 0.5,
                default => 0.0,
            };
        }
        return (int) round(($earned / count($checks)) * 100);
    }

    private static function summarize(array $checks): array
    {
        $summary = ['passes' => 0, 'warnings' => 0, 'failures' => 0];
        foreach ($checks as $c) {
            $summary[match ($c['status']) {
                'pass'  => 'passes',
                'warn'  => 'warnings',
                default => 'failures',
            }]++;
        }
        return $summary;
    }
}
