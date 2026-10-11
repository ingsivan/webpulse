<?php

namespace Database\Factories;

use App\Enums\IssueCategory;
use App\Enums\IssueSeverity;
use App\Models\Audit;
use App\Models\AuditIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditIssue>
 */
class AuditIssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audit_id' => Audit::factory(),
            'category' => IssueCategory::Seo,
            'severity' => IssueSeverity::Warning,
            'code' => 'missing_meta_description',
            'message' => 'La página no tiene meta description.',
            'url' => 'https://'.fake()->domainName(),
        ];
    }
}
