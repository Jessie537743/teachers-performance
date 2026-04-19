<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel 13 supports change() on enum columns natively (no doctrine/dbal required).
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->enum('status', ['provisioning', 'pending_activation', 'active', 'suspended', 'failed'])
                ->default('provisioning')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->enum('status', ['provisioning', 'active', 'suspended', 'failed'])
                ->default('provisioning')
                ->change();
        });
    }
};
