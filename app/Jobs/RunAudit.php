<?php

namespace App\Jobs;

use App\Enums\AuditStatus;
use App\Models\Audit;
use App\Services\Audit\SiteAuditor;
use App\Services\Audit\UptimeTracker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Runs a pending audit in the background and records uptime changes.
 */
class RunAudit implements ShouldQueue
{
    use Queueable;

    /**
     * Audits are not retried: a second run would duplicate its issues.
     */
    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public Audit $audit) {}

    public function handle(SiteAuditor $auditor, UptimeTracker $uptime): void
    {
        $audit = $auditor->run($this->audit);

        $uptime->record($audit);
    }

    public function failed(?Throwable $exception): void
    {
        $this->audit->update([
            'status' => AuditStatus::Failed,
            'progress' => 100,
            'error_message' => 'La auditoría falló por un error interno.',
            'finished_at' => now(),
        ]);
    }
}
