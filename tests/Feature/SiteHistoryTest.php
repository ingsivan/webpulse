<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SiteHistoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_shows_the_score_chart_and_every_audit(): void
    {
        $site = Site::factory()->create();
        Audit::factory()->for($site)->create(['overall_score' => 61]);
        Audit::factory()->for($site)->create(['overall_score' => 88]);
        Audit::factory()->for($site)->failed()->create();

        $this->get(route('sites.show', $site))
            ->assertOk()
            ->assertSee('<polyline', false)
            ->assertSee('Fallida')
            ->assertSeeInOrder(['88', '61']);
    }

    public function test_a_site_without_finished_audits_shows_an_empty_chart(): void
    {
        $site = Site::factory()->create();

        $this->get(route('sites.show', $site))
            ->assertOk()
            ->assertSee('Todavía no hay auditorías terminadas.');
    }

    public function test_a_private_site_is_only_visible_to_its_owner(): void
    {
        $site = Site::factory()->monitored()->create();

        $this->get(route('sites.show', $site))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('sites.show', $site))->assertNotFound();
        $this->actingAs($site->user)->get(route('sites.show', $site))->assertOk()->assertSee('Auditar ahora');
    }
}
