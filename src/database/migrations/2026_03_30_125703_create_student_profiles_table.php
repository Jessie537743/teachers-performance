<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('course', 100);
            $table->string('year_level', 50);
            $table->string('section', 50);
            $table->enum('student_status', ['regular', 'irregular'])->default('regular');
            $table->string('semester', 20);
            $table->string('school_year', 50);
            $table->string('last_promoted_school_year', 20)->nullable();
            $table->string('last_promoted_semester', 20)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
