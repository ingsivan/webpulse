<?php

namespace App\Services\Audit;

use Illuminate\Http\Client\Response;

/**
 * The outcome of a guarded request after following redirects.
 */
class FetchResult
{
    public function __construct(
        public readonly Response $response,
        public readonly string $finalUrl,
        public readonly int $durationMs,
        public readonly int $redirects,
    ) {}
}
