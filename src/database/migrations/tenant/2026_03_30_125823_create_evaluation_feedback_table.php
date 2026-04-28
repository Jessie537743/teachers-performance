<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('faculty_profiles')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->enum('evaluator_type', ['student', 'admin', 'dean', 'peer', 'self']);
            $table->string('school_year', 20)->nullable();
            $table->string('semester', 20)->nullable();
            $table->text('comment')->nullable();
            $table->enum('sentiment_label', ['positive', 'negative', 'neutral'])->nullable();
            $table->decimal('total_average', 4, 2)->nullable();
            $table->string('performance_level', 100)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['student_id', 'faculty_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_feedback');
    }
};
