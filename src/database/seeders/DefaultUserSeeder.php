<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding admin user...');

        // Use the actual bcrypt hash from the evaluation system dump
        // hash corresponds to the admin account
        DB::table('users')->insert([
            'name'                 => 'System Administrator',
            'email'                => 'admin@sample.com',
            'password'             => '$2y$12$X9zxuroF.Zzc4S86OPWK8eO9DubcAHxi1FdziYYJGOvWp7bwrs92G',
            'role'                 => 'admin',
            'is_active'            => true,
            'department_id'        => null,
            'must_change_password' => false,
            'created_at'           => '2026-03-07 07:38:59',
            'updated_at'           => now(),
        ]);

        $this->command->info('Admin user seeded.');
    }
}
