<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pending registration requests submitted via the public /register flow.
 *
 *  kind         student | personnel
 *  status       pending | approved | rejected
 *  payload      JSON of role-specific fields (course/year/section for students;
 *               role/department_position/personnel_type for personnel)
 *  password_hash already hashed at intake; approval copies it onto the User.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kind', 16);                 // student | personnel
            $table->string('name', 191);
            $table->string('email', 191);
            $table->string('password_hash');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->json('payload');
            $table->string('status', 16)->default('pending');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('decided_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['email', 'status'], 'reg_req_email_status_uq');
            $table->index(['kind', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_requests');
    }
};
