<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Assign the Human Resource role to the designated HR officer account when present.
     */
    public function up(): void
    {
        $email = 'rickydestacamento@smccnasipit.edu.ph';

        User::query()->where('email', $email)->update(['role' => 'human_resource']);
    }

    public function down(): void
    {
        // Intentionally left blank: do not revert role without knowing the previous value.
    }
};
