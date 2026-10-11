<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Site;
use App\Notifications\SiteDown;
use App\Notifications\SiteRecovered;
use App\Services\Audit\UptimeTracker;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UptimeTrackerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_emails_the_owner_when_a_monitored_site_goes_down(): void
    {
        $site = Site::factory()->monitored()->create();
        Notification::fake();

        app(UptimeTracker::class)->record(Audit::factory()->for($site)->failed()->create());

        $this->assertTrue($site->fresh()->is_down);
        Notification::assertSentTo($site->user, SiteDown::class);
    }

    public function test_an_http_error_counts_as_down(): void
    {
        $site = Site::factory()->monitored()->create();
        Notification::fake();

        app(UptimeTracker::class)->record(Audit::factory()->for($site)->create(['http_status' => 503]));

        Notification::assertSentTo($site->user, SiteDown::class);
    }

    public function test_does_not_repeat_the_alert_while_the_site_stays_down(): void
    {
        $site = Site::factory()->monitored()->create(['is_down' => true]);
        Notification::fake();

        app(UptimeTracker::class)->record(Audit::factory()->for($site)->failed()->create());

        Notification::assertNothingSent();
    }

    public function test_emails_the_owner_when_the_site_recovers(): void
    {
        $site = Site::factory()->monitored()->create(['is_down' => true]);
        Notification::fake();

        app(UptimeTracker::class)->record(Audit::factory()->for($site)->create());

        $this->assertFalse($site->fresh()->is_down);
        Notification::assertSentTo($site->user, SiteRecovered::class);
    }

    public function test_public_and_paused_sites_track_status_without_emailing(): void
    {
        $public = Site::factory()->create();
        $paused = Site::factory()->monitored()->create(['is_monitored' => false]);
        Notification::fake();

        app(UptimeTracker::class)->record(Audit::factory()->for($public)->failed()->create());
        app(UptimeTracker::class)->record(Audit::factory()->for($paused)->failed()->create());

        $this->assertTrue($public->fresh()->is_down);
        $this->assertTrue($paused->fresh()->is_down);
        Notification::assertNothingSent();
    }

    public function test_the_down_email_links_to_the_audit_and_explains_why(): void
    {
        $site = Site::factory()->monitored()->create(['name' => 'Mi tienda']);
        $audit = Audit::factory()->for($site)->failed()->create(['error_message' => 'Connection refused']);

        $mail = (new SiteDown($audit))->toMail($site->user);

        $this->assertSame('🔴 Mi tienda está caído', $mail->subject);
        $this->assertContains('Motivo: Connection refused', $mail->introLines);
        $this->assertSame(route('audits.show', $audit), $mail->actionUrl);
    }
}
