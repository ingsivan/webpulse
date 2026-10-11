<?php

namespace App\Services\Audit;

use App\Enums\IssueCategory;

/**
 * Turns findings into 0–100 scores: each category starts at 100 and loses
 * points per finding according to its severity.
 */
class ScoreCalculator
{
    private const PENALTIES = [
        'error' => 25,
        'warning' => 10,
        'info' => 2,
    ];

    /**
     * @param  list<AuditFinding>  $findings
     * @return array{performance_score: int, seo_score: int, links_score: int, overall_score: int}
     */
    public function calculate(array $findings): array
    {
        $scores = [
            'performance_score' => $this->categoryScore($findings, IssueCategory::Performance),
            'seo_score' => $this->categoryScore($findings, IssueCategory::Seo),
            'links_score' => $this->categoryScore($findings, IssueCategory::Links),
        ];

        return [...$scores, 'overall_score' => (int) round(array_sum($scores) / count($scores))];
    }

    /**
     * @param  list<AuditFinding>  $findings
     */
    private function categoryScore(array $findings, IssueCategory $category): int
    {
        $penalty = 0;

        foreach ($findings as $finding) {
            if ($finding->category === $category) {
                $penalty += self::PENALTIES[$finding->severity->value];
            }
        }

        return max(0, 100 - $penalty);
    }
}
