<?php

namespace App\Rules;

use App\Exceptions\UnsafeUrlException;
use App\Services\Audit\SafeHttpClient;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Rejects URLs that WebPulse must never request, such as hosts on a private network.
 */
class SafeUrl implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            app(SafeHttpClient::class)->assertSafe((string) $value);
        } catch (UnsafeUrlException $exception) {
            $fail($exception->getMessage());
        }
    }
}
