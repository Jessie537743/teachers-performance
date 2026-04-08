<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dean_evaluation_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dean_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('faculty_profiles')->cascadeOnDelete();
            $table->foreignId('criteria_id')->constrained('criteria')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->tinyInteger('rating');
            $table->string('semester', 20)->nullable();
            $table->string('school_year', 20)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['dean_user_id', 'faculty_id', 'question_id', 'semester', 'school_year'], 'dean_eval_answers_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dean_evaluation_answers');
    }
};
