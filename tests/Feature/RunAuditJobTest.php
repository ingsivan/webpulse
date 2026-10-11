<?php

namespace Tests\Feature;

use App\Enums\AuditStatus;
use App\Jobs\RunAudit;
use App\Models\Audit;
use App\Models\Site;
use App\Notifications\SiteDown;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class RunAuditJobTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_runs_the_audit_and_alerts_when_a_monitored_site_is_down(): void
    {
        Http::preventStrayRequests();
        $this->fakeDns(['shop.example.com' => ['93.184.216.34']]);
        Http::fake(['https://shop.example.com/' => fn () => throw new ConnectionException('Connection refused')]);
        $site = Site::factory()->monitored()->create(['url' => 'https://shop.example.com/']);
        $audit = Audit::factory()->for($site)->pending()->create();
        Notification::fake();

        RunAudit::dispatchSync($audit);

        $this->assertSame(AuditStatus::Failed, $audit->fresh()->status);
        Notification::assertSentTo($site->user, SiteDown::class);
    }

    public function test_marks_the_audit_failed_when_the_job_crashes(): void
    {
        $audit = Audit::factory()->pending()->create();

        (new RunAudit($audit))->failed(new RuntimeException('boom'));

        $audit->refresh();
        $this->assertSame(AuditStatus::Failed, $audit->status);
        $this->assertSame(100, $audit->progress);
        $this->assertSame('La auditoría falló por un error interno.', $audit->error_message);
    }
}
