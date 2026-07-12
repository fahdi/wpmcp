<?php

namespace WPMCP\Tools\Performance;

if (! defined('ABSPATH')) {
    exit;
}

class Analyzer
{
    private const CRITICAL_WEIGHT = 15;
    private const WARNING_WEIGHT  = 4;

    /**
     * Pure: counts, score, grade for a set of findings.
     *
     * @param array $findings Finding[]
     */
    public function summarize(array $findings): array
    {
        $counts = ['critical' => 0, 'warning' => 0, 'pass' => 0, 'info' => 0];
        foreach ($findings as $finding) {
            $status = (string) ($finding['status'] ?? 'info');
            if (isset($counts[ $status ])) {
                $counts[ $status ]++;
            }
        }

        $score = 100 - ($counts['critical'] * self::CRITICAL_WEIGHT) - ($counts['warning'] * self::WARNING_WEIGHT);
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
            'counts' => $counts,
            'score'  => $score,
            'grade'  => $grade,
        ];
    }
}
