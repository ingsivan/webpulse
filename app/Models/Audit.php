<?php

namespace App\Models;

use App\Enums\AuditStatus;
use Database\Factories\AuditFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'site_id', 'status', 'progress', 'http_status', 'load_time_ms', 'pages_crawled',
    'performance_score', 'seo_score', 'links_score', 'overall_score',
    'error_message', 'started_at', 'finished_at',
])]
class Audit extends Model
{
    /** @use HasFactory<AuditFactory> */
    use HasFactory, HasUlids;

    /**
     * Only the public `ulid` column is generated; `id` stays an auto-increment key.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected function casts(): array
    {
        return [
            'status' => AuditStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(AuditIssue::class);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [AuditStatus::Completed, AuditStatus::Failed], true);
    }

    /**
     * A site counts as down when the audit failed or the page answered with an HTTP error.
     */
    public function siteWasDown(): bool
    {
        return $this->status === AuditStatus::Failed || $this->http_status === null || $this->http_status >= 400;
    }
}
