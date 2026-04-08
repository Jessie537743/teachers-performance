<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_peer_evaluation_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluator_faculty_id')->constrained('faculty_profiles')->cascadeOnDelete();
            $table->foreignId('evaluatee_faculty_id')->constrained('faculty_profiles')->cascadeOnDelete();
            $table->enum('evaluation_type', ['self', 'peer']);
            $table->string('semester', 20)->nullable();
            $table->string('school_year', 20)->nullable();
            $table->text('comment')->nullable();
            $table->decimal('total_average', 4, 2)->nullable();
            $table->string('performance_level', 100)->nullable();
            $table->decimal('weighted_percentage', 5, 2)->default(10.00);
            $table->timestamp('created_at')->nullable();

            $table->unique(
                ['evaluator_faculty_id', 'evaluatee_faculty_id', 'evaluation_type', 'semester', 'school_year'],
                'faculty_peer_eval_feedback_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_peer_evaluation_feedback');
    }
};
