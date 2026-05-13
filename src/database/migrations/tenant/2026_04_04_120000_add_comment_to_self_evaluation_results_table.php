<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('self_evaluation_results', 'comment')) {
            return;
        }
        Schema::table('self_evaluation_results', function (Blueprint $table) {
            $table->text('comment')->nullable()->after('performance_level');
        });
    }

    public function down(): void
    {
        Schema::table('self_evaluation_results', function (Blueprint $table) {
            $table->dropColumn('comment');
        });
    }
};
