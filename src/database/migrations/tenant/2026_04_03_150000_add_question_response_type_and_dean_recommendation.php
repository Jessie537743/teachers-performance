<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('questions', 'response_type')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->string('response_type', 32)->default('likert')->after('question_text');
            });
        }

        if (!Schema::hasColumn('dean_evaluation_feedback', 'recommendation')) {
            Schema::table('dean_evaluation_feedback', function (Blueprint $table) {
                $table->string('recommendation', 32)->nullable()->after('comment');
            });
        }
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('response_type');
        });

        Schema::table('dean_evaluation_feedback', function (Blueprint $table) {
            $table->dropColumn('recommendation');
        });
    }
};
