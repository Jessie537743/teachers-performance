<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('tenants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('subdomain')->unique();
            $table->string('database');
            $table->enum('status', ['provisioning', 'active', 'suspended', 'failed'])
                ->default('provisioning');
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenants');
    }
};
