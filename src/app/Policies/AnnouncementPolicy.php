<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_SYSTEM)
            || $user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_DEPARTMENT);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_SYSTEM)
            || $user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_DEPARTMENT);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if ($user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_SYSTEM)) {
            return true;
        }
        if ($user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_DEPARTMENT)) {
            return $announcement->created_by === $user->id;
        }
        return false;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }

    /**
     * Validate that the submitted targeting payload is within the user's scope.
     *
     * @param array{everyone: bool, targets: array<array{target_type: string, target_id: string, is_exclude: bool}>} $payload
     */
    public function validateTargeting(User $user, array $payload): bool
    {
        if ($user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_SYSTEM)) {
            return true;
        }
        if (! $user->hasPermission(Permission::MANAGE_ANNOUNCEMENTS_DEPARTMENT)) {
            return false;
        }

        if ($payload['everyone'] ?? false) {
            return false;
        }
        $deptId = (string) ($user->department_id ?? '');
        if ($deptId === '') {
            return false;
        }

        foreach ($payload['targets'] ?? [] as $t) {
            $type = $t['target_type'] ?? null;
            $id   = (string) ($t['target_id'] ?? '');

            if ($type === 'role') {
                return false;
            }
            if ($type === 'department' && $id !== $deptId) {
                return false;
            }
            if ($type === 'user') {
                $member = User::find($id);
                if (! $member || (string) $member->department_id !== $deptId) {
                    return false;
                }
            }
        }

        return true;
    }
}
