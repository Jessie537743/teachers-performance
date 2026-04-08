<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_evaluation_summary', function (Blueprint $table) {
            $table->id();
            $table->integer('faculty_id');
            $table->integer('department_id');
            $table->string('semester', 20);
            $table->string('school_year', 20);
            $table->decimal('avg_score', 4, 2);
            $table->integer('total_responses');
            $table->decimal('previous_score', 4, 2)->nullable();
            $table->decimal('improvement_rate', 5, 2)->default(0);
            $table->dateTime('evaluation_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_evaluation_summary');
    }
};
