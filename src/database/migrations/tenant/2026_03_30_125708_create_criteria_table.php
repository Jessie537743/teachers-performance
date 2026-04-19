<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->enum('personnel_type', ['teaching', 'non-teaching'])->default('teaching');
            $table->enum('evaluator_group', ['student', 'dean', 'self', 'peer'])->default('student');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criteria');
    }
};
