<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;

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
            'created_at',
            'updated_at',
        ];
    }
}
