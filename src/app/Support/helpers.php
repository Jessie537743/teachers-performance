<?php

if (! function_exists('plan')) {
    /**
     * Resolve the PlanFeatures service for the current tenant.
     *
     *   plan()->has('ai_predictions')        — bool
     *   plan()->value('max_students')        — mixed (int|string|bool|null)
     *   plan()->within('max_students', $n)   — quota check
     *   plan()->planSlug()                   — 'free' | 'pro' | 'enterprise'
     */
    function plan(?\App\Models\Tenant $tenant = null): \App\Services\PlanFeatures
    {
        /** @var \App\Services\PlanFeatures $features */
        $features = app(\App\Services\PlanFeatures::class);

        if ($tenant) {
            return new class($features, $tenant) extends \App\Services\PlanFeatures {
                public function __construct(
                    private readonly \App\Services\PlanFeatures $base,
                    private readonly \App\Models\Tenant $boundTenant,
                ) {}
                public function has(string $capability, ?\App\Models\Tenant $tenant = null): bool
                {
                    return $this->base->has($capability, $tenant ?? $this->boundTenant);
                }
                public function value(string $capability, ?\App\Models\Tenant $tenant = null): mixed
                {
                    return $this->base->value($capability, $tenant ?? $this->boundTenant);
                }
                public function within(string $quota, int $current, ?\App\Models\Tenant $tenant = null): bool
                {
                    return $this->base->within($quota, $current, $tenant ?? $this->boundTenant);
                }
                public function planSlug(?\App\Models\Tenant $tenant = null): string
                {
                    return $this->base->planSlug($tenant ?? $this->boundTenant);
                }
                public function plan(?\App\Models\Tenant $tenant = null): array
                {
                    return $this->base->plan($tenant ?? $this->boundTenant);
                }
            };
        }

        return $features;
    }
}
