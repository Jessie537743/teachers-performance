<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_predictions', function (Blueprint $table) {
            $table->id();
            $table->integer('faculty_id');
            $table->integer('department_id');
            $table->string('semester', 20);
            $table->string('school_year', 20);
            $table->decimal('avg_score', 4, 2)->nullable();
            $table->integer('response_count')->nullable();
            $table->string('predicted_performance', 50)->nullable();
            $table->text('recommendation')->nullable();
            $table->string('model_used', 50)->default('Random Forest');
            $table->dateTime('prediction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_predictions');
    }
};
