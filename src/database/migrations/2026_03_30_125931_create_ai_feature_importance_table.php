<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_feature_importance', function (Blueprint $table) {
            $table->id();
            $table->string('model_name', 100)->nullable();
            $table->string('feature_name', 100)->nullable();
            $table->decimal('importance_score', 10, 6)->nullable();
            $table->string('semester', 20)->nullable();
            $table->string('school_year', 20)->nullable();
            $table->dateTime('recorded_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feature_importance');
    }
};
