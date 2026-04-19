<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code', 50);
            $table->string('subject_title', 150);
            $table->decimal('units', 3, 1)->nullable();

            $table->unique(['subject_code', 'subject_title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_catalog');
    }
};
