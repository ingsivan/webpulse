<?php

namespace Database\Seeders;

use App\Enums\IssueCategory;
use App\Enums\IssueSeverity;
use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo data so a fresh install shows real-looking dashboards and history.
 * Sign in as demo@webpulse.test with the password "password".
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::factory()->create([
            'name' => 'Cuenta demo',
            'email' => 'demo@webpulse.test',
        ]);

        foreach ([
            ['Mi tienda', 'https://tienda.example.com/', 82],
            ['Blog personal', 'https://blog.example.com/', 64],
            ['Portafolio', 'https://portafolio.example.com/', 93],
        ] as [$name, $url, $baseline]) {
            $site = Site::factory()->for($demo)->create([
                'name' => $name,
                'url' => $url,
                'is_monitored' => true,
                'last_checked_at' => now()->subMinutes(20),
            ]);

            $this->seedHistory($site, $baseline, 14);
        }

        foreach (['noticias.example.org' => 88, 'docs.example.net' => 71, 'cafeteria.example.com' => 95] as $host => $baseline) {
            $this->seedHistory(Site::factory()->create(['name' => $host, 'url' => "https://{$host}/"]), $baseline, 3);
        }
    }

    private function seedHistory(Site $site, int $baseline, int $days): void
    {
        foreach (range($days, 0) as $daysAgo) {
            $scores = collect(['performance_score', 'seo_score', 'links_score'])
                ->mapWithKeys(fn ($key) => [$key => max(0, min(100, $baseline + random_int(-12, 8)))]);

            $audit = Audit::factory()->for($site)->create([
                ...$scores,
                'overall_score' => (int) round($scores->avg()),
                'progress' => 100,
                'created_at' => now()->subDays($daysAgo)->subMinutes(random_int(0, 600)),
            ]);

            $this->seedIssues($audit);
        }
    }

    private function seedIssues(Audit $audit): void
    {
        $catalog = [
            [IssueCategory::Seo, IssueSeverity::Error, 'missing_meta_description', 'La página no tiene meta description.'],
            [IssueCategory::Seo, IssueSeverity::Warning, 'images_missing_alt', '3 imagen(es) no tienen texto alternativo (alt).'],
            [IssueCategory::Seo, IssueSeverity::Info, 'missing_canonical', 'Falta la URL canónica (<link rel="canonical">).'],
            [IssueCategory::Performance, IssueSeverity::Warning, 'heavy_image', 'La imagen pesa 812 KB.'],
            [IssueCategory::Performance, IssueSeverity::Warning, 'slow_page', 'La página tardó 2380 ms en responder.'],
            [IssueCategory::Links, IssueSeverity::Error, 'broken_link', 'Enlace roto (HTTP 404).'],
        ];

        foreach (array_rand($catalog, random_int(2, 4)) as $index) {
            [$category, $severity, $code, $message] = $catalog[$index];

            AuditIssue::factory()->for($audit)->create([
                'category' => $category,
                'severity' => $severity,
                'code' => $code,
                'message' => $message,
                'url' => $audit->site->url,
            ]);
        }
    }
}
