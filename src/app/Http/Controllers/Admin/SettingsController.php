<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-settings');

        $tab = $request->query('tab', 'general');

        $appName = Setting::get('app_name', 'Evaluation System');
        $appLogo = Setting::get('app_logo');

        $users = collect();
        $roleFilter = $request->query('role', '');
        $search = $request->query('search', '');

        if ($tab === 'users') {
            $query = User::with('department')->orderBy('name');

            if ($roleFilter) {
                $query->where('role', $roleFilter);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->paginate(25)->appends($request->query());
        }

        return view('settings.index', compact('tab', 'appName', 'appLogo', 'users', 'roleFilter', 'search'));
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        Gate::authorize('manage-settings');

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'app_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ]);

        Setting::set('app_name', $validated['app_name']);

        if ($request->hasFile('app_logo')) {
            // Delete old logo if it exists
            $oldLogo = Setting::get('app_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $path = $request->file('app_logo')->store('logos', 'public');
            Setting::set('app_logo', $path);
        }

        return redirect()->route('settings.index', ['tab' => 'general'])
            ->with('success', 'Settings updated successfully.');
    }

    public function resetUserPassword(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-settings');

        // Double-check: only admins may reset other users' passwords,
        // even if a non-admin somehow holds the manage-settings permission.
        abort_unless($request->user()->isAdmin(), 403, 'Only administrators can reset passwords.');

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password'             => Hash::make($validated['password']),
            'must_change_password' => true,
        ]);

        return redirect()
            ->route('settings.index', ['tab' => 'users'])
            ->with('success', "Password for {$user->name} has been reset. They will be asked to change it on next login.");
    }

    public function removeLogo(): RedirectResponse
    {
        Gate::authorize('manage-settings');

        $logo = Setting::get('app_logo');
        if ($logo && Storage::disk('public')->exists($logo)) {
            Storage::disk('public')->delete($logo);
        }

        Setting::set('app_logo', null);

        return redirect()->route('settings.index', ['tab' => 'general'])
            ->with('success', 'Logo removed. Default logo will be used.');
    }
}
