<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('read_at');
            $table->dateTime('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id'], 'ann_reads_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
    }
};
