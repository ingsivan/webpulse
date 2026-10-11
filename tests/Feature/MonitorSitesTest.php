<?php

namespace Tests\Feature;

use App\Jobs\RunAudit;
use App\Models\Site;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MonitorSitesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_queues_audits_only_for_monitored_sites_that_are_due(): void
    {
        $this->freezeSecond();
        $neverChecked = Site::factory()->monitored()->create(['last_checked_at' => null]);
        $due = Site::factory()->monitored()->create(['check_interval_minutes' => 60, 'last_checked_at' => now()->subMinutes(60)]);
        Site::factory()->monitored()->create(['check_interval_minutes' => 60, 'last_checked_at' => now()->subMinutes(59)]);
        Site::factory()->monitored()->create(['is_monitored' => false, 'last_checked_at' => null]);
        Site::factory()->create(['is_monitored' => true, 'last_checked_at' => null]);
        Queue::fake([RunAudit::class]);

        $this->artisan('webpulse:monitor')
            ->expectsOutput('Auditorías en cola: 2')
            ->assertSuccessful();

        Queue::assertPushed(RunAudit::class, 2);
        $this->assertSame(1, $neverChecked->audits()->count());
        $this->assertSame(1, $due->audits()->count());
    }

    public function test_is_scheduled_every_five_minutes(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('webpulse:monitor')
            ->assertSuccessful();
    }
}
