<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('criterion_evaluator_groups')) {
            Schema::create('criterion_evaluator_groups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
                $table->string('evaluator_group', 32);
                $table->unique(['criterion_id', 'evaluator_group']);
            });
        }

        // Only backfill while the source column still exists. A previous
        // half-applied run may have already dropped it, in which case the
        // join rows were inserted then; running again would only produce
        // unique-key violations.
        if (Schema::hasColumn('criteria', 'evaluator_group')) {
            foreach (DB::table('criteria')->select('id', 'evaluator_group')->get() as $row) {
                DB::table('criterion_evaluator_groups')->insertOrIgnore([
                    'criterion_id'    => $row->id,
                    'evaluator_group' => $row->evaluator_group,
                ]);
            }

            Schema::table('criteria', function (Blueprint $table) {
                $table->dropColumn('evaluator_group');
            });
        }
    }

    public function down(): void
    {
        Schema::table('criteria', function (Blueprint $table) {
            $table->string('evaluator_group', 32)->default('student');
        });

        foreach (DB::table('criteria')->pluck('id') as $criterionId) {
            $first = DB::table('criterion_evaluator_groups')
                ->where('criterion_id', $criterionId)
                ->orderBy('id')
                ->value('evaluator_group');

            DB::table('criteria')
                ->where('id', $criterionId)
                ->update(['evaluator_group' => $first ?? 'student']);
        }

        Schema::dropIfExists('criterion_evaluator_groups');
    }
};
