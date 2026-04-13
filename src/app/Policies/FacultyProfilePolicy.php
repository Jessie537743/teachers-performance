<?php

namespace App\Policies;

use App\Models\FacultyProfile;
use App\Models\User;

class FacultyProfilePolicy
{
    /**
     * Dean/Head can only view faculty in their department.
     * Faculty can view their own profile.
     * Admin can view all profiles.
     */
    public function view(User $user, FacultyProfile $faculty): bool
    {
        if ($user->isAdmin()) return true;

        if ($user->hasRole(['dean', 'head'])) {
            return $faculty->department_id === $user->department_id;
        }

        if ($user->isFaculty()) {
            return $faculty->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, FacultyProfile $faculty): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, FacultyProfile $faculty): bool
    {
        return $user->isAdmin();
    }
}
