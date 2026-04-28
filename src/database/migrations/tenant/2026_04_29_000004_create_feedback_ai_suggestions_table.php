<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache for per-comment AI improvement suggestions.
 *
 *  - Keyed by SHA-256 of the *normalized* comment text so identical phrasing
 *    across multiple feedback rows reuses the same analysis (free-tier
 *    deterministic NLP can rebuild it cheaply, but caching keeps page renders
 *    instant and avoids re-running theme detection on every load).
 *  - `variant_seed` lets "Regenerate" pick a different phrasing without
 *    invalidating the cache for other viewers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_ai_suggestions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('comment_hash', 64);
            $table->unsignedTinyInteger('variant_seed')->default(0);
            $table->string('polarity', 16);              // positive | negative | neutral
            $table->string('source_kind', 32);           // student | dean | self | peer
            $table->text('summary');
            $table->json('suggested_actions');
            $table->text('root_cause')->nullable();
            $table->json('themes')->nullable();          // detected criterion themes
            $table->string('engine', 32)->default('local-nlp-v1');
            $table->timestamps();

            $table->unique(['comment_hash', 'variant_seed'], 'feedback_ai_suggestions_hash_seed_uq');
            $table->index('polarity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_ai_suggestions');
    }
};
