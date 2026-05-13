<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `awaiting_activation` and `awaiting_payment` to the tenants.status enum.
 *
 * Lifecycle, after this change:
 *   awaiting_activation → user submitted the subscribe form, NO database
 *                         provisioned yet. Heavy work deferred until they
 *                         redeem the activation code.
 *   provisioning        → activation is in flight (DB being created /
 *                         migrations running).
 *   pending_activation  → DB exists, waiting for redemption.
 *                         (Used by the super-admin "Create Tenant" flow,
 *                         which still provisions up front.)
 *   active              → admin user created, tenant is live.
 *   awaiting_payment    → subscription payment failed; access is being held.
 *   suspended           → operator-paused.
 *   failed              → provisioning errored.
 *
 * The new `awaiting_activation` value is what stops bot subscriptions from
 * eating MySQL disk: spam signups never reach `provisioning`, so no schema
 * is created until someone proves intent by clicking the email link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->enum('status', [
                'awaiting_activation',
                'provisioning',
                'pending_activation',
                'active',
                'awaiting_payment',
                'suspended',
                'failed',
            ])->default('provisioning')->change();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->enum('status', [
                'provisioning',
                'pending_activation',
                'active',
                'suspended',
                'failed',
            ])->default('provisioning')->change();
        });
    }
};
