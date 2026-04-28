<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->string('billing_cycle', 16)->nullable()->after('plan');     // monthly | yearly | null (free/enterprise)
            $table->string('subscription_status', 16)->default('none')->after('billing_cycle'); // active | grace | canceled | none
            $table->timestamp('current_period_start')->nullable()->after('subscription_status');
            $table->timestamp('current_period_end')->nullable()->after('current_period_start');
            $table->timestamp('next_charge_at')->nullable()->after('current_period_end');
            $table->timestamp('last_charge_at')->nullable()->after('next_charge_at');

            $table->index(['subscription_status', 'next_charge_at'], 'tenants_billing_due_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->dropIndex('tenants_billing_due_idx');
            $table->dropColumn([
                'billing_cycle',
                'subscription_status',
                'current_period_start',
                'current_period_end',
                'next_charge_at',
                'last_charge_at',
            ]);
        });
    }
};
