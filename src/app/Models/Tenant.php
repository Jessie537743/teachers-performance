<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $connection = 'central';
    protected $table = 'tenants';

    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * Columns NOT stored in the `data` JSON bag.
     * Everything else gets transparently moved into `data` by stancl.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'subdomain',
            'database',
            'status',
            'plan',
            'created_at',
            'updated_at',
        ];
    }

    public function provisioningJobs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TenantProvisioningJob::class);
    }

    public function activationCodes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\ActivationCode::class);
    }

    public function currentUnredeemedCode(): ?\App\Models\ActivationCode
    {
        return $this->activationCodes()
            ->where('status', 'unredeemed')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }
}
