<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Configurable weights for criteria + questions.
 *
 *  - `criteria.weight`  → percent contribution of this criterion to the overall
 *                         GWA inside its evaluator-group + personnel-type bucket.
 *  - `questions.weight` → percent contribution of this question to its parent
 *                         criterion's category average.
 *
 * Both default to 0, but the backfill below seeds equal-share defaults so
 * existing reports continue to produce identical numbers as before. Weights
 * are normalized at compute time (see App\Services\WeightedScoringService),
 * so they don't have to sum to exactly 100 — the system divides through.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('criteria', function (Blueprint $table) {
            $table->decimal('weight', 5, 2)->default(0)->after('name');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->decimal('weight', 5, 2)->default(0)->after('question_text');
        });

        // Backfill: equal-share defaults so existing reports stay numerically identical.
        $criteriaCount = DB::table('criteria')->count();
        if ($criteriaCount > 0) {
            $criterionShare = round(100 / $criteriaCount, 2);
            DB::table('criteria')->update(['weight' => $criterionShare]);
        }

        // Within each criterion, split 100 across its questions.
        $questionGroups = DB::table('questions')
            ->select('criteria_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('criteria_id')
            ->get();
        foreach ($questionGroups as $group) {
            $share = round(100 / max(1, (int) $group->cnt), 2);
            DB::table('questions')->where('criteria_id', $group->criteria_id)->update(['weight' => $share]);
        }
    }

    public function down(): void
    {
        Schema::table('criteria', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
