<?php

use App\Enums\Permission;
use App\Models\Department;
use App\Models\FacultyProfile;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * Assigns School President and VP Academic to the ADMINISTRATOR department and ensures
 * faculty profiles exist so they can use self-evaluation and institution-wide dean evaluation.
 */
return new class extends Migration
{
    public function up(): void
    {
        $admin = Department::firstOrCreate(
            ['code' => 'ADMIN'],
            [
                'name'            => 'ADMINISTRATOR',
                'department_type' => 'non-teaching',
                'is_active'       => true,
            ]
        );

        $leaders = [
            'president@smccnasipit.edu.ph'   => 'school_president',
            'vpacademics@smccnasipit.edu.ph' => 'vp_acad',
        ];

        foreach ($leaders as $email => $role) {
            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                continue;
            }

            $user->update([
                'role'          => $role,
                'department_id' => $admin->id,
            ]);

            $profile = FacultyProfile::query()->where('user_id', $user->id)->first();
            $payload = [
                'department_id'       => $admin->id,
                'department_position' => 'dean_head',
            ];

            if ($profile) {
                $profile->update($payload);
            } else {
                FacultyProfile::create(array_merge($payload, [
                    'user_id'    => $user->id,
                    'created_at' => now(),
                ]));
            }
        }

        Permission::clearCache('school_president');
        Permission::clearCache('vp_acad');
    }

    public function down(): void
    {
        // Intentionally empty: do not strip executive roles or profiles automatically.
    }
};
