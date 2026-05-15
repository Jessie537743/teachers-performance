<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Departmental Plan — a dean/head's roll-up of action items for their whole
 * department for a given evaluation period.
 *
 *   departmental_plans       one record per (department, period, dean)
 *   departmental_plan_items  individual action items, optionally tied to a faculty
 *
 *   plan.status   draft | active | completed | archived
 *   item.status   pending | in_progress | completed | cancelled
 *   item.category recognition | sustained_excellence | training | coaching
 *                 | pip | promotion | reassignment | retention | dept_wide
 *   item.priority high | medium | low
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('departmental_plans')) {
            Schema::create('departmental_plans', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('department_id');
                $table->unsignedBigInteger('dean_user_id');
                $table->string('school_year', 16);
                $table->string('semester', 32);

                $table->text('summary');
                $table->json('roll_up')->nullable(); // counts: total, by_level, by_priority, by_recommendation
                $table->json('generated_from')->nullable(); // input snapshot for traceability

                $table->string('model_version', 32)->default('dept-plan-v1');
                $table->string('status', 16)->default('draft');

                $table->unsignedBigInteger('generated_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->timestamp('completed_at')->nullable();

                $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
                $table->foreign('dean_user_id')->references('id')->on('users')->onDelete('cascade');

                $table->index(['department_id', 'school_year', 'semester'], 'dp_dept_period_idx');
                $table->index('status', 'dp_status_idx');
            });
        }

        if (!Schema::hasTable('departmental_plan_items')) {
            Schema::create('departmental_plan_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('departmental_plan_id');
                $table->unsignedBigInteger('faculty_profile_id')->nullable();

                $table->string('category', 32);
                $table->string('priority', 16);
                $table->string('title', 255);
                $table->text('description');
                $table->json('programs')->nullable();   // suggested professional development programs
                $table->json('source')->nullable();     // performance_level, weighted_avg, dean_recommendation snapshot

                $table->string('status', 16)->default('pending');
                $table->date('due_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->timestamp('completed_at')->nullable();

                $table->foreign('departmental_plan_id')->references('id')->on('departmental_plans')->onDelete('cascade');
                $table->foreign('faculty_profile_id')->references('id')->on('faculty_profiles')->onDelete('set null');

                $table->index('departmental_plan_id', 'dpi_plan_idx');
                $table->index('faculty_profile_id', 'dpi_faculty_idx');
                $table->index('status', 'dpi_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('departmental_plan_items');
        Schema::dropIfExists('departmental_plans');
    }
};
