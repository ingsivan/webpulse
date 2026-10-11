<?php

namespace App\Models;

use App\Enums\AuditStatus;
use Database\Factories\AuditFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'site_id', 'status', 'http_status', 'load_time_ms', 'pages_crawled',
    'performance_score', 'seo_score', 'links_score', 'overall_score',
    'error_message', 'started_at', 'finished_at',
])]
class Audit extends Model
{
    /** @use HasFactory<AuditFactory> */
    use HasFactory;

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
}
