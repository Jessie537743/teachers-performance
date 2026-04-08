<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_peer_evaluation_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluator_faculty_id')->constrained('faculty_profiles')->cascadeOnDelete();
            $table->foreignId('evaluatee_faculty_id')->constrained('faculty_profiles')->cascadeOnDelete();
            $table->enum('evaluation_type', ['self', 'peer']);
            $table->foreignId('criteria_id')->constrained('criteria')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->tinyInteger('rating');
            $table->string('semester', 20)->nullable();
            $table->string('school_year', 20)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(
                ['evaluator_faculty_id', 'evaluatee_faculty_id', 'evaluation_type', 'question_id', 'semester', 'school_year'],
                'faculty_peer_eval_answers_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_peer_evaluation_answers');
    }
};
