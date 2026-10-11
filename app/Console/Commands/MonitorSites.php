<?php

namespace App\Console\Commands;

use App\Jobs\RunAudit;
use App\Models\Site;
use App\Services\Audit\SiteAuditor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('webpulse:monitor')]
#[Description('Queue an audit for every monitored site whose check interval has passed')]
class MonitorSites extends Command
{
    public function handle(SiteAuditor $auditor): int
    {
        $queued = 0;

        Site::query()
            ->where('is_monitored', true)
            ->whereNotNull('user_id')
            ->lazyById()
            ->filter(fn (Site $site) => $site->isDueForCheck())
            ->each(function (Site $site) use ($auditor, &$queued) {
                RunAudit::dispatch($auditor->start($site));
                $queued++;
            });

        $this->info("Auditorías en cola: {$queued}");

        return self::SUCCESS;
    }
}
