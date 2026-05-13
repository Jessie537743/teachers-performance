<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds an `auth_mode` column to `api_integrations` plus the extra columns
 * the two new modes need:
 *
 *   - api_key            (existing)  — single shared key
 *   - key_and_secret     (new)       — sends two headers, e.g.
 *                                       X-API-Key: <api_key>
 *                                       X-API-Secret: <api_secret>
 *   - basic              (new)       — sends Authorization: Basic
 *                                       base64(api_key:api_secret)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_integrations', function (Blueprint $table) {
            if (!Schema::hasColumn('api_integrations', 'auth_mode')) {
                $table->string('auth_mode', 32)->default('api_key')->after('base_url');
            }
            if (!Schema::hasColumn('api_integrations', 'api_secret')) {
                // Encrypted via Eloquent cast (same as api_key). Nullable
                // because api_key mode doesn't need it.
                $table->text('api_secret')->nullable()->after('api_key');
            }
            if (!Schema::hasColumn('api_integrations', 'secret_header_name')) {
                $table->string('secret_header_name', 64)->nullable()->after('header_prefix');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_integrations', function (Blueprint $table) {
            if (Schema::hasColumn('api_integrations', 'secret_header_name')) {
                $table->dropColumn('secret_header_name');
            }
            if (Schema::hasColumn('api_integrations', 'api_secret')) {
                $table->dropColumn('api_secret');
            }
            if (Schema::hasColumn('api_integrations', 'auth_mode')) {
                $table->dropColumn('auth_mode');
            }
        });
    }
};
