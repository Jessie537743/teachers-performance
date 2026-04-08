<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained('faculty_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_assignments');
    }
};
