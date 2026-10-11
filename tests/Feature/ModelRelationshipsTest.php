<?php

namespace Tests\Feature;

use App\Enums\AuditStatus;
use App\Enums\IssueCategory;
use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_has_audits_and_latest_audit(): void
    {
        $site = Site::factory()->create();
        Audit::factory()->for($site)->create(['created_at' => now()->subDay()]);
        $latest = Audit::factory()->for($site)->create();

        $this->assertCount(2, $site->audits);
        $this->assertTrue($site->latestAudit->is($latest));
    }

    public function test_audit_issues_cast_enums_and_details(): void
    {
        $issue = AuditIssue::factory()->create(['details' => ['size_kb' => 820]]);

        $this->assertSame(AuditStatus::Completed, $issue->audit->status);
        $this->assertSame(IssueCategory::Seo, $issue->category);
        $this->assertSame(820, $issue->fresh()->details['size_kb']);
    }

    public function test_deleting_a_site_removes_its_audits_and_issues(): void
    {
        $issue = AuditIssue::factory()->create();
        $issue->audit->site->delete();

        $this->assertDatabaseCount('audits', 0);
        $this->assertDatabaseCount('audit_issues', 0);
    }
}
