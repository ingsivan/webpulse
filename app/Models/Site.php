<?php

namespace App\Models;

use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'name', 'url', 'is_monitored', 'check_interval_minutes', 'last_checked_at'])]
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_monitored' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    public function latestAudit(): HasOne
    {
        return $this->hasOne(Audit::class)->latestOfMany();
    }
}
