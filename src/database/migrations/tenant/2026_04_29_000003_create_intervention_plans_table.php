<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI-generated intervention plans for low-performing faculty.
 *
 *  severity         critical | high | moderate
 *  action_items     JSON list of { phase, criterion, item, recommended_intervention, source_question_ids[] }
 *  expected_outcome JSON { current_avg, target_avg, current_level, predicted_level, lift_pct, ml_confidence }
 *  status           draft | active | completed | superseded
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervention_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('faculty_id');
            $table->string('school_year', 16);
            $table->string('semester', 32);
            $table->string('severity', 16);
            $table->text('summary');
            $table->json('action_items');
            $table->json('expected_outcome')->nullable();
            $table->json('signal_clusters')->nullable();      // grouped weak-item themes
            $table->string('model_version', 32)->default('plan-v1');
            $table->string('status', 16)->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->timestamp('completed_at')->nullable();

            $table->foreign('faculty_id')->references('id')->on('faculty_profiles')->onDelete('cascade');
            $table->index(['faculty_id', 'school_year', 'semester']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervention_plans');
    }
};
