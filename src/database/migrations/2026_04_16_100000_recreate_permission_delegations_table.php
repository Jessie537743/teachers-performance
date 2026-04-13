<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the orphaned permission_delegations table (left over from a previous
 * feature removal) and recreate it fresh for the restored delegation feature.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('permission_delegations');

        Schema::create('permission_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('delegatee_id')->constrained('users')->cascadeOnDelete();
            $table->json('permissions');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['delegatee_id', 'revoked_at']);
            $table->index(['delegator_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_delegations');
    }
};
