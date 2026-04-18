<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('tenant_provisioning_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->enum('status', ['pending', 'running', 'succeeded', 'failed'])
                ->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_provisioning_jobs');
    }
};
