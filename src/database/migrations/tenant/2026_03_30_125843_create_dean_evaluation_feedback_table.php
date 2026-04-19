<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dean_evaluation_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dean_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('faculty_profiles')->cascadeOnDelete();
            $table->string('semester', 20)->nullable();
            $table->string('school_year', 20)->nullable();
            $table->text('comment')->nullable();
            $table->decimal('total_average', 4, 2)->nullable();
            $table->string('performance_level', 100)->nullable();
            $table->decimal('weighted_percentage', 5, 2)->default(40.00);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['dean_user_id', 'faculty_id', 'semester', 'school_year'], 'dean_eval_feedback_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dean_evaluation_feedback');
    }
};
