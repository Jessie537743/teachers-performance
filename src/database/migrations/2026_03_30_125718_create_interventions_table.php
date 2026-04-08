<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->string('indicator', 255)->nullable();
            $table->text('meaning_low_score')->nullable();
            $table->text('recommended_intervention')->nullable();
            $table->text('basis')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};
