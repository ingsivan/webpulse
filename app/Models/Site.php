<?php

namespace App\Models;

use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'name', 'url', 'is_monitored', 'check_interval_minutes', 'is_down', 'last_checked_at'])]
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
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
            'is_monitored' => 'boolean',
            'is_down' => 'boolean',
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

    /**
     * Anonymous audits from the home page belong to no user and are public.
     */
    public function isPublic(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Whether a monitored site's last check is older than its interval.
     */
    public function isDueForCheck(): bool
    {
        return $this->is_monitored
            && ($this->last_checked_at === null
                || $this->last_checked_at->lte(now()->subMinutes($this->check_interval_minutes)));
    }
}
