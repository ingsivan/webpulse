<?php

namespace App\Models;

use App\Enums\IssueCategory;
use App\Enums\IssueSeverity;
use Database\Factories\AuditIssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['audit_id', 'category', 'severity', 'code', 'message', 'url', 'details'])]
class AuditIssue extends Model
{
    /** @use HasFactory<AuditIssueFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'category' => IssueCategory::class,
            'severity' => IssueSeverity::class,
            'details' => 'array',
        ];
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
