<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Self-heal from a half-applied state. A previous run may have
        // added the column / created the table but failed to record the
        // migration row (most often when a network or deploy was
        // interrupted between statements). Guarding each step keeps the
        // tenant migration command crash-loop-proof.
        if (!Schema::hasColumn('users', 'date_of_birth')) {
            Schema::table('users', function (Blueprint $table) {
                $table->date('date_of_birth')->nullable()->after('must_change_password');
            });
        }

        if (!Schema::hasTable('password_reset_requests')) {
            Schema::create('password_reset_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('new_password_hash');
                $table->enum('status', ['pending', 'approved', 'declined'])->default('pending')->index();
                $table->text('admin_notes')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_requests');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('date_of_birth');
        });
    }
};
