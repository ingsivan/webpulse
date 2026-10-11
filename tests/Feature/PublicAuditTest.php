<?php

namespace Tests\Feature;

use App\Enums\AuditStatus;
use App\Enums\IssueSeverity;
use App\Jobs\RunAudit;
use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PublicAuditTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_home_lists_recent_public_audits_once_per_site_and_hides_private_ones(): void
    {
        $public = Site::factory()->create(['name' => 'publico.example']);
        Audit::factory()->for($public)->count(2)->create();
        Audit::factory()->for(Site::factory()->monitored()->create(['name' => 'privado.example']))->create();

        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'publico.example'));
        $response->assertDontSee('privado.example');
    }

    public function test_queues_an_audit_for_a_typed_url(): void
    {
        Queue::fake([RunAudit::class]);
        $this->fakeDns(['example.com' => ['93.184.216.34']]);

        $response = $this->post(route('audits.store'), ['url' => '  Example.com ']);

        $site = Site::sole();
        $audit = Audit::sole();
        $this->assertSame('https://example.com/', $site->url);
        $this->assertSame('example.com', $site->name);
        $this->assertNull($site->user_id);
        $this->assertSame(AuditStatus::Pending, $audit->status);
        $response->assertRedirect(route('audits.show', $audit));
        Queue::assertPushed(RunAudit::class, fn (RunAudit $job) => $job->audit->is($audit));
    }

    public function test_reuses_the_public_site_for_the_same_url(): void
    {
        Queue::fake([RunAudit::class]);
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        $site = Site::factory()->create(['url' => 'https://example.com/']);

        $this->post(route('audits.store'), ['url' => 'https://EXAMPLE.com']);

        $this->assertSame(1, Site::count());
        $this->assertSame(1, $site->audits()->count());
    }

    public function test_requires_a_url(): void
    {
        $this->post(route('audits.store'), ['url' => ''])
            ->assertSessionHasErrors(['url' => 'Escribe la dirección del sitio que quieres auditar.']);
    }

    public function test_refuses_an_internal_address(): void
    {
        Queue::fake([RunAudit::class]);

        $this->post(route('audits.store'), ['url' => 'http://127.0.0.1:6379'])
            ->assertSessionHasErrors(['url' => 'El dominio 127.0.0.1 apunta a una dirección interna y no se puede auditar.']);

        $this->assertSame(0, Site::count());
        Queue::assertNothingPushed();
    }

    public function test_limits_audits_per_visitor(): void
    {
        Queue::fake([RunAudit::class]);
        $this->fakeDns(['example.com' => ['93.184.216.34']]);

        foreach (range(1, 3) as $attempt) {
            $this->post(route('audits.store'), ['url' => 'example.com'])->assertRedirect();
        }

        $this->from(route('home'))
            ->post(route('audits.store'), ['url' => 'example.com'])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['url' => 'Hiciste muchas auditorías seguidas. Espera un momento e inténtalo de nuevo.']);
        Queue::assertPushed(RunAudit::class, 3);
    }

    public function test_a_running_audit_shows_its_progress(): void
    {
        $audit = Audit::factory()->pending()->create(['status' => AuditStatus::Running, 'progress' => 40]);

        $this->get(route('audits.show', $audit))
            ->assertOk()
            ->assertSee('Analizando el sitio')
            ->assertSee(route('audits.status', $audit))
            ->assertDontSee('Descargar PDF');
    }

    public function test_a_finished_audit_shows_scores_and_escaped_issues(): void
    {
        $audit = Audit::factory()->create(['overall_score' => 77]);
        AuditIssue::factory()->for($audit)->create([
            'severity' => IssueSeverity::Error,
            'message' => 'Falta <title> en la página.',
        ]);

        $this->get(route('audits.show', $audit))
            ->assertOk()
            ->assertSee('77')
            ->assertSee('Falta &lt;title&gt; en la página.', false)
            ->assertDontSee('Falta <title>', false)
            ->assertSee(route('audits.report', $audit));
    }

    public function test_a_failed_audit_explains_why(): void
    {
        $audit = Audit::factory()->failed()->create(['error_message' => 'No se pudo resolver el dominio nope.invalid.']);

        $this->get(route('audits.show', $audit))
            ->assertOk()
            ->assertSee('No pudimos auditar el sitio')
            ->assertSee('No se pudo resolver el dominio nope.invalid.');
    }

    public function test_status_endpoint_reports_progress(): void
    {
        $audit = Audit::factory()->pending()->create(['status' => AuditStatus::Running, 'progress' => 55]);

        $this->getJson(route('audits.status', $audit))
            ->assertOk()
            ->assertExactJson(['status' => 'running', 'progress' => 55, 'finished' => false]);
    }

    public function test_report_lists_every_issue(): void
    {
        $audit = Audit::factory()->create();
        AuditIssue::factory()->for($audit)->create(['message' => 'Enlace roto (HTTP 404).', 'url' => 'https://example.com/missing']);

        $this->get(route('audits.report', $audit))
            ->assertOk()
            ->assertSee('Reporte técnico')
            ->assertSee('Enlace roto (HTTP 404).')
            ->assertSee('https://example.com/missing');
    }

    public function test_audits_of_a_private_site_are_hidden_from_other_people(): void
    {
        $audit = Audit::factory()->for(Site::factory()->monitored())->create();

        $this->get(route('audits.show', $audit))->assertNotFound();
        $this->getJson(route('audits.status', $audit))->assertNotFound();
        $this->get(route('audits.report', $audit))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('audits.show', $audit))->assertNotFound();
        $this->actingAs($audit->site->user)->get(route('audits.show', $audit))->assertOk();
    }

    public function test_audits_are_not_reachable_by_numeric_id(): void
    {
        $audit = Audit::factory()->create();

        $this->get("/audits/{$audit->id}")->assertNotFound();
    }
}
