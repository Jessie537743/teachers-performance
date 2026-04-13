<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Models\AuditLog;
use App\Http\Controllers\Controller;
use App\Models\PermissionDelegation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PermissionDelegationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-roles');

        $users = User::where('is_active', true)
            ->whereJsonDoesntContain('roles', 'student')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'roles']);

        $delegations = PermissionDelegation::with(['delegator:id,name,roles', 'delegatee:id,name,roles'])
            ->orderByDesc('created_at')
            ->paginate(25);

        $permissionGroups = Permission::allPermissions();

        return view('roles.delegations', compact('users', 'delegations', 'permissionGroups'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-roles');

        $data = $request->validate([
            'delegator_id'  => ['required', 'exists:users,id'],
            'delegatee_id'  => ['required', 'different:delegator_id', 'exists:users,id'],
            'permissions'   => ['required', 'array', 'min:1'],
            'permissions.*' => ['string'],
            'starts_at'     => ['nullable', 'date'],
            'expires_at'    => ['nullable', 'date', 'after:starts_at'],
        ]);

        // Whitelist the permissions against the known set.
        $valid = [];
        foreach (Permission::allPermissions() as $group) {
            $valid = array_merge($valid, array_keys($group));
        }
        $permissions = array_values(array_intersect($data['permissions'], $valid));

        if (empty($permissions)) {
            return back()->with('error', 'No valid permissions selected.');
        }

        // Delegator must actually hold every permission they delegate
        // (admins have the Gate::before bypass, so treat them as holding all).
        $delegator = User::findOrFail($data['delegator_id']);
        if (! $delegator->isAdmin()) {
            $held = Permission::forRoles($delegator->roles ?? []);
            $missing = array_diff($permissions, $held);
            if (! empty($missing)) {
                return back()->with('error', 'The delegator does not hold: ' . implode(', ', $missing));
            }
        }

        // Prevent duplicate active delegations for the same pair
        $existingActive = PermissionDelegation::active()
            ->where('delegator_id', $data['delegator_id'])
            ->where('delegatee_id', $data['delegatee_id'])
            ->first();

        if ($existingActive) {
            return back()->with('error', 'An active delegation already exists between these users. Revoke it first.');
        }

        $delegation = PermissionDelegation::create([
            'delegator_id' => $data['delegator_id'],
            'delegatee_id' => $data['delegatee_id'],
            'permissions'  => $permissions,
            'starts_at'    => $data['starts_at'] ?? null,
            'expires_at'   => $data['expires_at'] ?? null,
        ]);

        $delegatee = User::findOrFail($data['delegatee_id']);
        AuditLog::log('delegated', "Delegation created: {$delegator->name} → {$delegatee->name}", $delegation);

        return back()->with('success', 'Delegation created successfully.');
    }

    public function revoke(PermissionDelegation $delegation): RedirectResponse
    {
        Gate::authorize('manage-roles');

        if ($delegation->revoked_at === null) {
            $delegation->update(['revoked_at' => now()]);
        }

        $delegation->loadMissing(['delegator', 'delegatee']);
        AuditLog::log('revoked', "Delegation revoked: {$delegation->delegator->name} → {$delegation->delegatee->name}", $delegation);

        return back()->with('success', 'Delegation revoked.');
    }
}
