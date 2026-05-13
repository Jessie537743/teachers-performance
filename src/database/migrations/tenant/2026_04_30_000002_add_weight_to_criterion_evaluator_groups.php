<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each criterion may belong to multiple evaluator groups (student/peer/dean/self).
 * Previously a single `criteria.weight` was shared across all of them, which
 * meant editing weight on one tab silently mutated every other tab. Move the
 * weight onto the join row so each (criterion, evaluator_group) pair carries
 * its own value. Existing rows keep the legacy criterion-level weight as their
 * starting point.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('criterion_evaluator_groups', 'weight')) {
            Schema::table('criterion_evaluator_groups', function (Blueprint $table) {
                $table->decimal('weight', 5, 2)->nullable()->after('evaluator_group');
            });
        }

        DB::table('criterion_evaluator_groups')
            ->whereNull('weight')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $weight = DB::table('criteria')->where('id', $row->criterion_id)->value('weight');
                    DB::table('criterion_evaluator_groups')
                        ->where('id', $row->id)
                        ->update(['weight' => $weight ?? 0]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('criterion_evaluator_groups', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
