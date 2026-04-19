<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criterion_personnel_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
            $table->string('personnel_type', 32);
            $table->unique(['criterion_id', 'personnel_type']);
        });

        foreach (DB::table('criteria')->select('id', 'personnel_type')->get() as $row) {
            DB::table('criterion_personnel_types')->insert([
                'criterion_id'    => $row->id,
                'personnel_type' => $row->personnel_type,
            ]);
        }

        Schema::table('criteria', function (Blueprint $table) {
            $table->dropColumn('personnel_type');
        });
    }

    public function down(): void
    {
        Schema::table('criteria', function (Blueprint $table) {
            $table->string('personnel_type', 32)->default('teaching');
        });

        foreach (DB::table('criteria')->pluck('id') as $criterionId) {
            $first = DB::table('criterion_personnel_types')
                ->where('criterion_id', $criterionId)
                ->orderBy('id')
                ->value('personnel_type');

            DB::table('criteria')
                ->where('id', $criterionId)
                ->update(['personnel_type' => $first ?? 'teaching']);
        }

        Schema::dropIfExists('criterion_personnel_types');
    }
};
