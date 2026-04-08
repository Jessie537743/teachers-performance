<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_evaluation_results', function (Blueprint $table) {
            $table->id();
            $table->integer('faculty_id');
            $table->integer('department_id');
            $table->string('semester', 20);
            $table->string('school_year', 20);
            $table->decimal('total_average', 4, 2);
            $table->string('performance_level', 100);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_evaluation_results');
    }
};
