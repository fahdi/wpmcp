<?php

namespace WPMCP\Tools\Security;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Security & Malware Scanner orchestrator.
 *
 * resolve_checks(), summarize(), and group_by_category() are pure. scan() wires
 * the four audits together (added once the audits exist). Read-only: this class
 * never writes, never mutates, and never executes scanned content.
 */
class Security_Scanner
{
    private const CRITICAL_WEIGHT   = 20;
    private const WARNING_WEIGHT    = 5;
    private const CATEGORY_CRIT_CAP = 60;
    private const TOP_RECS          = 8;

    private const ALL_CHECKS = ['malware', 'integrity', 'hardening', 'software'];

    /**
     * Pure: normalize the requested checks to a canonical-ordered valid subset.
     *
     * @param string[]|null $requested
     * @return string[]
     */
    public function resolve_checks(?array $requested): array
    {
        if (empty($requested)) {
            return self::ALL_CHECKS;
        }
        $valid = [];
        foreach (self::ALL_CHECKS as $check) {
            if (in_array($check, $requested, true)) {
                $valid[] = $check;
            }
        }
        return empty($valid) ? self::ALL_CHECKS : $valid;
    }

    /**
     * Pure: counts, score (per-category critical cap), grade, ranked recs.
     *
     * @param array $findings Finding[]
     * @return array { counts, score, grade, top_recommendations }
     */
    public function summarize(array $findings): array
    {
        $counts       = ['critical' => 0, 'warning' => 0, 'pass' => 0, 'info' => 0];
        $cat_crit_pen = [];
        $warn_penalty = 0;

        foreach ($findings as $finding) {
            $status = (string) ($finding['status'] ?? 'info');
            if (isset($counts[ $status ])) {
                $counts[ $status ]++;
            }
            if ('critical' === $status) {
                $category                  = (string) ($finding['category'] ?? 'malware');
                $cat_crit_pen[ $category ] = min(
                    self::CATEGORY_CRIT_CAP,
                    ($cat_crit_pen[ $category ] ?? 0) + self::CRITICAL_WEIGHT
                );
            } elseif ('warning' === $status) {
                $warn_penalty += self::WARNING_WEIGHT;
            }
        }

        $score = 100 - array_sum($cat_crit_pen) - $warn_penalty;
        $score = max(0, min(100, $score));

        if ($score >= 90) {
            $grade = 'A';
        } elseif ($score >= 80) {
            $grade = 'B';
        } elseif ($score >= 70) {
            $grade = 'C';
        } elseif ($score >= 60) {
            $grade = 'D';
        } else {
            $grade = 'F';
        }

        return [
            'counts'              => $counts,
            'score'               => $score,
            'grade'               => $grade,
            'top_recommendations' => $this->rank_recommendations($findings),
        ];
    }

    /**
     * Pure: bucket findings by their category, always returning all four keys.
     *
     * @param array $findings Finding[]
     * @return array category => Finding[]
     */
    public function group_by_category(array $findings): array
    {
        $sections = ['malware' => [], 'integrity' => [], 'hardening' => [], 'software' => []];
        foreach ($findings as $finding) {
            $category = (string) ($finding['category'] ?? 'malware');
            if (! isset($sections[ $category ])) {
                $sections[ $category ] = [];
            }
            $sections[ $category ][] = $finding;
        }
        return $sections;
    }

    /**
     * @param array $findings Finding[]
     * @return string[]
     */
    private function rank_recommendations(array $findings): array
    {
        $critical = [];
        $warning  = [];
        foreach ($findings as $finding) {
            $recommendation = trim((string) ($finding['recommendation'] ?? ''));
            if ('' === $recommendation) {
                continue;
            }
            $line = sprintf('[%s] %s', (string) ($finding['label'] ?? ''), $recommendation);
            if ('critical' === ($finding['status'] ?? '')) {
                $critical[] = $line;
            } elseif ('warning' === ($finding['status'] ?? '')) {
                $warning[] = $line;
            }
        }
        return array_slice(array_merge($critical, $warning), 0, self::TOP_RECS);
    }
}
