<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR e-signatures used to sign generated reports.
 *
 *  user_id          owner of the signature (HR staff)
 *  title            free text label rendered under the name (e.g. "Head, Human Resource")
 *  signature_path   public-disk path to the uploaded signature image
 *  is_signatory     exactly one row may be true at a time — the active report signatory
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title', 191)->default('Head, Human Resource');
            $table->string('signature_path', 255)->nullable();
            $table->boolean('is_signatory')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique('user_id');
            $table->index('is_signatory');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
