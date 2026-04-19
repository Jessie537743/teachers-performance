<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code', 50);
            $table->string('course_name', 255);
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('year_levels', 50)->nullable();
            $table->string('semester', 20)->nullable();
            $table->string('school_year', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['course_code', 'department_id', 'semester', 'school_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
