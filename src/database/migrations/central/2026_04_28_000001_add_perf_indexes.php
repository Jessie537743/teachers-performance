<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for the platform console.
 *
 *  - tenants.plan                  — filtered + grouped by /admin/tenants and /admin/plans
 *  - tenants(status, created_at)   — KPI tile counts + ORDER BY id list
 *  - activation_codes.status       — /admin/plans status filter
 *  - activation_codes.expires_at   — currentUnredeemedCode() lookup
 */
return new class extends Migration
{
    public function up(): void
    {
        $existing = $this->existingIndexes('tenants');
        Schema::connection('central')->table('tenants', function (Blueprint $table) use ($existing) {
            if (! in_array('tenants_plan_index', $existing, true)) {
                $table->index('plan');
            }
            if (! in_array('tenants_status_created_at_index', $existing, true)) {
                $table->index(['status', 'created_at']);
            }
        });

        $existing = $this->existingIndexes('activation_codes');
        Schema::connection('central')->table('activation_codes', function (Blueprint $table) use ($existing) {
            if (! in_array('activation_codes_status_index', $existing, true)) {
                $table->index('status');
            }
            if (! in_array('activation_codes_expires_at_index', $existing, true)) {
                $table->index('expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->dropIndex(['plan']);
            $table->dropIndex(['status', 'created_at']);
        });
        Schema::connection('central')->table('activation_codes', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['expires_at']);
        });
    }

    private function existingIndexes(string $table): array
    {
        $rows = DB::connection('central')->select("SHOW INDEX FROM `{$table}`");
        return array_unique(array_map(fn ($r) => $r->Key_name, $rows));
    }
};
