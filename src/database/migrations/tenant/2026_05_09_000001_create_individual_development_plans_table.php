<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Individual Development Plans (IDP) — generated per (faculty, period).
 *
 * Sibling of `intervention_plans` but broader: every faculty member gets one
 * for growth planning, not just at-risk performers. `engine` records which
 * generator produced this row (e.g. local-template-v1 today, anthropic /
 * openai later) so the artifact is traceable when the provider is swapped.
 *
 *  status   draft | active | completed | superseded
 *  engine   local-template-v1 | anthropic-claude | openai-gpt | ...
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('individual_development_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('faculty_id');
            $table->string('school_year', 16);
            $table->string('semester', 32);

            $table->text('summary');
            $table->json('strengths');                  // [{area, evidence, score}]
            $table->json('growth_areas');               // [{area, current_level, target_level, gap, evidence}]
            $table->json('goals');                      // SMART goals
            $table->json('action_items');               // [{phase, action, resources, owner, due}]
            $table->json('expected_outcomes')->nullable();
            $table->json('recommended_resources')->nullable();
            $table->json('generated_from')->nullable(); // input snapshot for traceability

            $table->string('engine', 32)->default('local-template-v1');
            $table->string('model_version', 32)->default('idp-v1');
            $table->string('status', 16)->default('draft');

            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamps();
            $table->timestamp('completed_at')->nullable();

            $table->foreign('faculty_id')->references('id')->on('faculty_profiles')->onDelete('cascade');
            $table->index(['faculty_id', 'school_year', 'semester']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('individual_development_plans');
    }
};
