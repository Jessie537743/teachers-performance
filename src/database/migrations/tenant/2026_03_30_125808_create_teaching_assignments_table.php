<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_offering_id')->constrained('subject_offerings')->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('faculty_profiles')->cascadeOnDelete();

            $table->unique(['subject_offering_id', 'faculty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_assignments');
    }
};
