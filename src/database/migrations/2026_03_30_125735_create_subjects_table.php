<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->nullable();
            $table->string('title', 255)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('course', 50)->nullable();
            $table->string('year_level', 20)->nullable();
            $table->string('section', 10)->default('1');
            $table->string('semester', 20)->nullable();
            $table->string('school_year', 20)->nullable();
            $table->integer('catalog_id')->nullable();

            $table->unique(['code', 'department_id', 'course', 'year_level', 'semester', 'school_year'], 'subjects_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
