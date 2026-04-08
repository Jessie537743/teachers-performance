<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'human_resource', 'school_president', 'vp_acad', 'vp_admin']);
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Department $department): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->role === 'admin';
    }
}
