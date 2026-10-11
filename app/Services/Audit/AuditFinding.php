<?php

namespace App\Services\Audit;

use App\Enums\IssueCategory;
use App\Enums\IssueSeverity;

/**
 * A single problem found during an audit, before it is stored as an AuditIssue.
 */
class AuditFinding
{
    /**
     * @param  array<string, mixed>|null  $details
     */
    public function __construct(
        public readonly IssueCategory $category,
        public readonly IssueSeverity $severity,
        public readonly string $code,
        public readonly string $message,
        public readonly ?string $url = null,
        public readonly ?array $details = null,
    ) {}

    /**
     * @return array{category: IssueCategory, severity: IssueSeverity, code: string, message: string, url: ?string, details: ?array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'severity' => $this->severity,
            'code' => $this->code,
            'message' => $this->message,
            'url' => $this->url,
            'details' => $this->details,
        ];
    }
}
