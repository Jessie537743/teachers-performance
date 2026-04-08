<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_model_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('model_name', 100);
            $table->string('semester', 20)->nullable();
            $table->string('school_year', 20)->nullable();
            $table->decimal('accuracy', 5, 4)->nullable();
            $table->decimal('precision_score', 5, 4)->nullable();
            $table->decimal('recall_score', 5, 4)->nullable();
            $table->decimal('f1_score', 5, 4)->nullable();
            $table->integer('training_samples')->nullable();
            $table->integer('testing_samples')->nullable();
            $table->string('model_version', 50)->nullable();
            $table->dateTime('training_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_model_metrics');
    }
};
