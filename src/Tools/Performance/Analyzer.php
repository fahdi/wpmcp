<?php

namespace WPMCP\Tools\Performance;

if (! defined('ABSPATH')) {
    exit;
}

class Analyzer
{
    private const CRITICAL_WEIGHT = 15;
    private const WARNING_WEIGHT  = 4;
    private const TOP_RECOMMENDATIONS = 8;

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
            'counts'              => $counts,
            'score'               => $score,
            'grade'               => $grade,
            'top_recommendations' => $this->rank_recommendations($findings),
        ];
    }

    /**
     * Pure: is $url on $site_host?
     */
    public function validate_same_host(string $url, string $site_host): bool
    {
        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        return '' !== $host && strtolower($host) === strtolower($site_host);
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

        return array_slice(array_merge($critical, $warning), 0, self::TOP_RECOMMENDATIONS);
    }
}
