<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subscriptions ledger — one row per billing period (initial signup + each renewal).
 * Acts as both invoice history and audit trail. Failed-charge attempts also
 * write a row with status=failed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('plan', 32);
            $table->string('billing_cycle', 16);                 // monthly | yearly
            $table->unsignedInteger('amount_cents');             // store as cents to avoid float math
            $table->string('currency', 3)->default('USD');
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->string('status', 16)->default('paid');       // paid | failed | refunded
            $table->timestamp('paid_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->string('reference', 64)->nullable();         // simulated charge ref / future Stripe id
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onDelete('cascade');
            $table->index(['tenant_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('subscriptions');
    }
};
