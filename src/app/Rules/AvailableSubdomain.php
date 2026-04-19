<?php

namespace App\Rules;

use App\Models\Tenant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AvailableSubdomain implements ValidationRule
{
    private const RESERVED = ['admin', 'www', 'api', 'app', 'mail', 'ftp', 'cdn', 'assets', 'static'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,30}[a-z0-9])?$/', $value)) {
            $fail('The :attribute must be 2-32 chars, lowercase letters/digits/hyphens, not starting or ending with a hyphen.');
            return;
        }

        $lower = strtolower($value);

        if (in_array($lower, self::RESERVED, true)) {
            $fail("The subdomain ':input' is reserved and cannot be used.");
            return;
        }

        if (Tenant::where('subdomain', $lower)->exists()) {
            $fail("The subdomain ':input' is already taken.");
        }
    }
}
