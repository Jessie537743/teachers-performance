<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_catalog_id')->constrained('subject_catalog')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('course', 50);
            $table->string('year_level', 20);
            $table->string('section', 50)->nullable();
            $table->string('semester', 20);
            $table->string('school_year', 20);
            $table->boolean('is_active')->default(true);

            $table->unique(
                ['subject_catalog_id', 'department_id', 'course', 'year_level', 'section', 'semester', 'school_year'],
                'subject_offerings_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_offerings');
    }
};
