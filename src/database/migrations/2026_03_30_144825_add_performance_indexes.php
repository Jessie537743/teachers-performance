<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // users — composite indexes for role-based filtering queries.
        // department_id is already indexed by its FK constraint; skip single-column index.
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'is_active'], 'users_role_active_idx');
            $table->index(['role', 'is_active', 'department_id'], 'users_role_active_dept_idx');
        });

        // evaluation_feedback — faculty_id single-column index is already covered by the FK
        // constraint. The composite index on (faculty_id, semester, school_year) is the
        // high-value addition for AVG aggregation queries scoped to a period.
        Schema::table('evaluation_feedback', function (Blueprint $table) {
            $table->index(['faculty_id', 'semester', 'school_year'], 'ef_faculty_period_idx');
        });

        // dean_evaluation_feedback — same pattern as evaluation_feedback.
        Schema::table('dean_evaluation_feedback', function (Blueprint $table) {
            $table->index(['faculty_id', 'semester', 'school_year'], 'def_faculty_period_idx');
        });

        // faculty_peer_evaluation_feedback — evaluatee_faculty_id single-column index is
        // covered by its FK. Add composite indexes for AVG queries that filter by type and period.
        Schema::table('faculty_peer_evaluation_feedback', function (Blueprint $table) {
            $table->index(['evaluatee_faculty_id', 'evaluation_type'], 'fpef_evaluatee_type_idx');
            $table->index(
                ['evaluatee_faculty_id', 'evaluation_type', 'semester', 'school_year'],
                'fpef_evaluatee_type_period_idx'
            );
        });

        // self_evaluation_results — faculty_id is a plain integer column (no FK constraint),
        // so no index was auto-created. Both single-column and composite are needed.
        Schema::table('self_evaluation_results', function (Blueprint $table) {
            $table->index('faculty_id', 'ser_faculty_idx');
            $table->index(['faculty_id', 'semester', 'school_year'], 'ser_faculty_period_idx');
        });

        // peer_evaluation_results — faculty_id is a plain integer (no FK).
        Schema::table('peer_evaluation_results', function (Blueprint $table) {
            $table->index('faculty_id', 'per_faculty_idx');
        });

        // subjects — department_id FK index already exists. Add the period composite for
        // queries that scope subjects by semester/school_year.
        Schema::table('subjects', function (Blueprint $table) {
            $table->index(['semester', 'school_year'], 'subjects_period_idx');
        });

        // evaluation_periods — composite index supporting the common is_open + date range lookup.
        Schema::table('evaluation_periods', function (Blueprint $table) {
            $table->index(['is_open', 'start_date', 'end_date'], 'ep_open_dates_idx');
        });

        // faculty_evaluation_summary — faculty_id is a plain integer (no FK).
        Schema::table('faculty_evaluation_summary', function (Blueprint $table) {
            $table->index('faculty_id', 'fes_faculty_idx');
            $table->index(['faculty_id', 'semester', 'school_year'], 'fes_faculty_period_idx');
        });

        // faculty_predictions — faculty_id is a plain integer (no FK).
        Schema::table('faculty_predictions', function (Blueprint $table) {
            $table->index('faculty_id', 'fp_faculty_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_active_idx');
            $table->dropIndex('users_role_active_dept_idx');
        });

        Schema::table('evaluation_feedback', function (Blueprint $table) {
            $table->dropIndex('ef_faculty_period_idx');
        });

        Schema::table('dean_evaluation_feedback', function (Blueprint $table) {
            $table->dropIndex('def_faculty_period_idx');
        });

        Schema::table('faculty_peer_evaluation_feedback', function (Blueprint $table) {
            $table->dropIndex('fpef_evaluatee_type_idx');
            $table->dropIndex('fpef_evaluatee_type_period_idx');
        });

        Schema::table('self_evaluation_results', function (Blueprint $table) {
            $table->dropIndex('ser_faculty_idx');
            $table->dropIndex('ser_faculty_period_idx');
        });

        Schema::table('peer_evaluation_results', function (Blueprint $table) {
            $table->dropIndex('per_faculty_idx');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex('subjects_period_idx');
        });

        Schema::table('evaluation_periods', function (Blueprint $table) {
            $table->dropIndex('ep_open_dates_idx');
        });

        Schema::table('faculty_evaluation_summary', function (Blueprint $table) {
            $table->dropIndex('fes_faculty_idx');
            $table->dropIndex('fes_faculty_period_idx');
        });

        Schema::table('faculty_predictions', function (Blueprint $table) {
            $table->dropIndex('fp_faculty_idx');
        });
    }
};
