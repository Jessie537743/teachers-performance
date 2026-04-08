<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criterion_evaluator_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
            $table->string('evaluator_group', 32);
            $table->unique(['criterion_id', 'evaluator_group']);
        });

        foreach (DB::table('criteria')->select('id', 'evaluator_group')->get() as $row) {
            DB::table('criterion_evaluator_groups')->insert([
                'criterion_id'    => $row->id,
                'evaluator_group' => $row->evaluator_group,
            ]);
        }

        Schema::table('criteria', function (Blueprint $table) {
            $table->dropColumn('evaluator_group');
        });
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
