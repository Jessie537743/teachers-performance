<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('body_markdown');
            $table->text('body_html');
            $table->enum('priority', ['info', 'normal', 'critical'])->default('normal');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('everyone')->default(false);
            $table->boolean('show_on_login')->default(false);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->dateTime('publish_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'publish_at', 'expires_at'], 'ann_active_idx');
            $table->index(['show_on_login', 'status'], 'ann_login_idx');
            $table->index(['is_pinned', 'publish_at'], 'ann_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
