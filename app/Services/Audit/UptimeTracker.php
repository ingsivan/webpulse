<?php

namespace App\Services\Audit;

use App\Models\Audit;
use App\Notifications\SiteDown;
use App\Notifications\SiteRecovered;

/**
 * Notices when a monitored site goes down or comes back, and emails its owner once per change.
 */
class UptimeTracker
{
    public function record(Audit $audit): void
    {
        $site = $audit->site;
        $isDown = $audit->siteWasDown();

        if ($isDown === $site->is_down) {
            return;
        }

        $site->update(['is_down' => $isDown]);

        if (! $site->is_monitored || $site->user === null) {
            return;
        }

        $site->user->notify($isDown ? new SiteDown($audit) : new SiteRecovered($audit));
    }
}
