<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks which guided tours a user has completed. JSON list of tour keys,
 * e.g. ["admin", "settings"]. The auto-show logic checks for the user's
 * role-specific key (admin / dean / faculty / student / hr) and skips
 * auto-launch if it's already in the list. Users can replay any tour
 * manually via the user menu regardless of completion status.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'completed_tours')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('completed_tours')->nullable()->after('must_change_password');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'completed_tours')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('completed_tours');
            });
        }
    }
};
