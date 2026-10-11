<?php

namespace Database\Factories;

use App\Enums\AuditStatus;
use App\Models\Site;
use App\Models\Audit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Audit>
 */
class AuditFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'status' => AuditStatus::Completed,
            'http_status' => 200,
            'load_time_ms' => fake()->numberBetween(150, 4000),
            'pages_crawled' => fake()->numberBetween(1, 25),
            'performance_score' => fake()->numberBetween(40, 100),
            'seo_score' => fake()->numberBetween(40, 100),
            'links_score' => fake()->numberBetween(40, 100),
            'overall_score' => fake()->numberBetween(40, 100),
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ];
    }
}
