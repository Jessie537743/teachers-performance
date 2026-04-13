<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sentiment_lexicon', function (Blueprint $table) {
            $table->id();
            $table->string('word', 191);
            $table->enum('polarity', ['positive', 'negative', 'neutral']);
            $table->string('language', 12)->nullable()->comment('en, fil, ceb, mixed, etc.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('word');
            $table->index(['polarity', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sentiment_lexicon');
    }
};
