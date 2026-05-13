<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * API Integration scaffold.
 *
 * Creates a single-row-per-tenant configuration table that an Admin fills in
 * from Settings → API Integration. Adds `external_id` to the four resources
 * we can import (departments, courses, subjects, student_profiles) so a future
 * sync can upsert by the external system's stable ID without colliding with
 * locally-managed records.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->default('External System');
            $table->string('base_url', 512);

            // Auth — API key in header. `header_name` and `header_prefix`
            // let the admin match the external system's expectation
            // ("Authorization: Bearer <key>" vs "X-API-Key: <key>").
            $table->text('api_key'); // encrypted via Eloquent cast
            $table->string('header_name', 64)->default('Authorization');
            $table->string('header_prefix', 32)->default('Bearer ');

            // Per-resource paths appended to base_url. Stored as JSON so we
            // can add resources later without another migration.
            $table->json('resource_paths')->nullable();

            $table->boolean('is_active')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_sync_resource', 32)->nullable();
            $table->string('last_sync_status', 16)->nullable(); // success | error
            $table->text('last_sync_error')->nullable();
            $table->json('last_sync_stats')->nullable(); // { created, updated, skipped, errors }

            $table->timestamps();
        });

        // external_id columns on the four target tables.
        // Nullable + indexed so local records (created via the UI) coexist
        // with externally-managed ones and the sync can fast-lookup.

        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'external_id')) {
                $table->string('external_id', 128)->nullable()->after('id');
                $table->index('external_id', 'departments_ext_id_idx');
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'external_id')) {
                $table->string('external_id', 128)->nullable()->after('id');
                $table->index('external_id', 'courses_ext_id_idx');
            }
        });

        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'external_id')) {
                $table->string('external_id', 128)->nullable()->after('id');
                $table->index('external_id', 'subjects_ext_id_idx');
            }
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('student_profiles', 'external_id')) {
                $table->string('external_id', 128)->nullable()->after('id');
                $table->index('external_id', 'student_profiles_ext_id_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('student_profiles', 'external_id')) {
                $table->dropIndex('student_profiles_ext_id_idx');
                $table->dropColumn('external_id');
            }
        });
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'external_id')) {
                $table->dropIndex('subjects_ext_id_idx');
                $table->dropColumn('external_id');
            }
        });
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'external_id')) {
                $table->dropIndex('courses_ext_id_idx');
                $table->dropColumn('external_id');
            }
        });
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'external_id')) {
                $table->dropIndex('departments_ext_id_idx');
                $table->dropColumn('external_id');
            }
        });

        Schema::dropIfExists('api_integrations');
    }
};
