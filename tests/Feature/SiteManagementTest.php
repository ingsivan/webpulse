<?php

namespace Tests\Feature;

use App\Http\Requests\StoreSiteRequest;
use App\Jobs\RunAudit;
use App\Models\Audit;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SiteManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_lists_only_the_users_sites(): void
    {
        $user = User::factory()->create();
        Site::factory()->for($user)->create(['name' => 'Mi tienda']);
        Site::factory()->monitored()->create(['name' => 'Sitio ajeno']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mi tienda')
            ->assertDontSee('Sitio ajeno');
    }

    public function test_adding_a_site_starts_monitoring_and_its_first_audit(): void
    {
        Queue::fake([RunAudit::class]);
        $this->fakeDns(['shop.example.com' => ['93.184.216.34']]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('sites.store'), ['url' => 'shop.example.com', 'check_interval_minutes' => 30])
            ->assertRedirect(route('dashboard'));

        $site = $user->sites()->sole();
        $this->assertSame('https://shop.example.com/', $site->url);
        $this->assertSame('shop.example.com', $site->name);
        $this->assertTrue($site->is_monitored);
        $this->assertSame(30, $site->check_interval_minutes);
        Queue::assertPushed(RunAudit::class, fn (RunAudit $job) => $job->audit->site->is($site));
    }

    public function test_a_user_cannot_add_the_same_site_twice(): void
    {
        $this->fakeDns(['shop.example.com' => ['93.184.216.34']]);
        $user = User::factory()->create();
        Site::factory()->for($user)->create(['url' => 'https://shop.example.com/']);

        $this->actingAs($user)
            ->post(route('sites.store'), ['url' => 'https://shop.example.com', 'check_interval_minutes' => 60])
            ->assertSessionHasErrors(['url' => 'Ya estás monitoreando este sitio.']);
    }

    public function test_a_user_can_monitor_a_limited_number_of_sites(): void
    {
        $this->fakeDns(['one-more.example.com' => ['93.184.216.34']]);
        $user = User::factory()->create();
        Site::factory()->for($user)->count(StoreSiteRequest::MAX_SITES_PER_USER)->create();

        $this->actingAs($user)
            ->post(route('sites.store'), ['url' => 'one-more.example.com', 'check_interval_minutes' => 60])
            ->assertSessionHasErrors(['url' => 'Puedes monitorear hasta 10 sitios.']);
    }

    public function test_the_check_interval_must_come_from_the_list(): void
    {
        $this->fakeDns(['shop.example.com' => ['93.184.216.34']]);

        $this->actingAs(User::factory()->create())
            ->post(route('sites.store'), ['url' => 'shop.example.com', 'check_interval_minutes' => 1])
            ->assertSessionHasErrors(['check_interval_minutes' => 'Elige una frecuencia de la lista.']);
    }

    public function test_an_owner_can_pause_monitoring(): void
    {
        $site = Site::factory()->monitored()->create();

        $this->actingAs($site->user)
            ->put(route('sites.update', $site), ['name' => 'Nuevo nombre', 'is_monitored' => 0, 'check_interval_minutes' => 360])
            ->assertRedirect(route('dashboard'));

        $site->refresh();
        $this->assertFalse($site->is_monitored);
        $this->assertSame('Nuevo nombre', $site->name);
        $this->assertSame(360, $site->check_interval_minutes);
    }

    public function test_an_owner_can_delete_a_site_with_its_history(): void
    {
        $site = Site::factory()->monitored()->create();
        Audit::factory()->for($site)->create();

        $this->actingAs($site->user)->delete(route('sites.destroy', $site))->assertRedirect(route('dashboard'));

        $this->assertModelMissing($site);
        $this->assertSame(0, Audit::count());
    }

    public function test_an_owner_can_audit_a_site_on_demand(): void
    {
        Queue::fake([RunAudit::class]);
        $site = Site::factory()->monitored()->create();

        $response = $this->actingAs($site->user)->post(route('sites.audits.store', $site));

        $response->assertRedirect(route('audits.show', $site->audits()->sole()));
        Queue::assertPushed(RunAudit::class);
    }

    public function test_other_users_cannot_change_someone_elses_site(): void
    {
        Queue::fake([RunAudit::class]);
        $site = Site::factory()->monitored()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->put(route('sites.update', $site), ['name' => 'Hackeado', 'is_monitored' => 0, 'check_interval_minutes' => 60])
            ->assertForbidden();
        $this->actingAs($intruder)->delete(route('sites.destroy', $site))->assertForbidden();
        $this->actingAs($intruder)->post(route('sites.audits.store', $site))->assertForbidden();

        $this->assertModelExists($site);
        $this->assertTrue($site->fresh()->is_monitored);
        Queue::assertNothingPushed();
    }

    public function test_guests_cannot_manage_sites(): void
    {
        $site = Site::factory()->monitored()->create();

        $this->post(route('sites.store'), ['url' => 'example.com'])->assertRedirect(route('login'));
        $this->delete(route('sites.destroy', $site))->assertRedirect(route('login'));
    }
}
