<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->enum('target_type', ['role', 'department', 'user']);
            $table->string('target_id', 64);
            $table->boolean('is_exclude')->default(false);
            $table->timestamps();

            $table->unique(
                ['announcement_id', 'target_type', 'target_id', 'is_exclude'],
                'ann_targets_unique'
            );
            $table->index(['announcement_id', 'is_exclude'], 'ann_targets_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_targets');
    }
};
