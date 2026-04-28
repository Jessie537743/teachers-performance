<?php

namespace App\Services;

use App\Models\Tenant;

/**
 * Single entry point for "can the current tenant do X?" checks.
 *
 * Resolution order for the active tenant:
 *   1. Argument passed to the method (explicit override)
 *   2. tenant() — Stancl helper, set inside any subdomain-bound request
 *   3. null     — falls back to free-plan capabilities (safest default)
 *
 * Capability values come from config/plans.php → capabilities map.
 */
class PlanFeatures
{
    public function has(string $capability, ?Tenant $tenant = null): bool
    {
        $value = $this->value($capability, $tenant);

        // Truthy "advanced"/"basic" string values count as on; only false/null is off.
        if (is_bool($value)) {
            return $value;
        }

        return $value !== null && $value !== false && $value !== '';
    }

    public function value(string $capability, ?Tenant $tenant = null): mixed
    {
        $plan = $this->planSlug($tenant);

        return config("plans.{$plan}.capabilities.{$capability}");
    }

    /**
     * Quota check: is `$current` still within the plan's limit for `$quota`?
     * Returns true when the limit is null (unlimited) or current < limit.
     */
    public function within(string $quota, int $current, ?Tenant $tenant = null): bool
    {
        $limit = $this->value($quota, $tenant);

        if ($limit === null) {
            return true;
        }

        if (! is_int($limit)) {
            return true;
        }

        return $current < $limit;
    }

    public function planSlug(?Tenant $tenant = null): string
    {
        $tenant ??= function_exists('tenant') ? tenant() : null;

        if ($tenant && isset($tenant->plan) && config("plans.{$tenant->plan}")) {
            return $tenant->plan;
        }

        return 'free';
    }

    public function plan(?Tenant $tenant = null): array
    {
        return config("plans.{$this->planSlug($tenant)}", []);
    }
}
