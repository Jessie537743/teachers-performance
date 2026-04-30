<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
            $query = User::with('department')
                ->whereJsonDoesntContain('roles', 'student')
                ->orderBy('name');

            if ($roleFilter) {
                $query->whereJsonContains('roles', $roleFilter);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->paginate(25)->appends($request->query());
        }

        $hrUsers = collect();
        $signatures = collect();
        $activeSignatory = null;
        if ($tab === 'signatures') {
            $hrUsers = User::whereJsonContains('roles', 'human_resource')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $signatures = Signature::with('user')
                ->whereIn('user_id', $hrUsers->pluck('id'))
                ->get()
                ->keyBy('user_id');

            $activeSignatory = Signature::activeSignatory();
        }

        return view('settings.index', compact(
            'tab', 'appName', 'appLogo', 'users', 'roleFilter', 'search',
            'hrUsers', 'signatures', 'activeSignatory'
        ));
    }

    public function uploadSignature(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-settings');

        abort_unless($user->isHumanResource(), 403, 'Signatures are only for HR users.');

        $validated = $request->validate([
            'title'     => ['nullable', 'string', 'max:191'],
            'signature' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $signature = Signature::firstOrNew(['user_id' => $user->id]);

        if ($signature->signature_path && Storage::disk('public')->exists($signature->signature_path)) {
            Storage::disk('public')->delete($signature->signature_path);
        }

        $signature->signature_path = $request->file('signature')->store('signatures', 'public');
        $signature->title = $validated['title'] ?: ($signature->title ?: 'Head, Human Resource');
        $signature->save();

        return redirect()->route('settings.index', ['tab' => 'signatures'])
            ->with('success', "Signature saved for {$user->name}.");
    }

    public function drawSignature(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-settings');

        abort_unless($user->isHumanResource(), 403, 'Signatures are only for HR users.');

        $validated = $request->validate([
            'title'          => ['nullable', 'string', 'max:191'],
            'signature_data' => ['required', 'string', 'starts_with:data:image/png;base64,'],
        ]);

        $base64 = substr($validated['signature_data'], strlen('data:image/png;base64,'));
        $binary = base64_decode($base64, true);

        if ($binary === false || strlen($binary) < 200) {
            return redirect()->back()
                ->withErrors(['signature_data' => 'Signature is empty or could not be decoded.']);
        }

        if (strlen($binary) > 2 * 1024 * 1024) {
            return redirect()->back()
                ->withErrors(['signature_data' => 'Drawn signature exceeds 2MB.']);
        }

        $signature = Signature::firstOrNew(['user_id' => $user->id]);

        if ($signature->signature_path && Storage::disk('public')->exists($signature->signature_path)) {
            Storage::disk('public')->delete($signature->signature_path);
        }

        $path = 'signatures/'.uniqid('sig_', true).'.png';
        Storage::disk('public')->put($path, $binary);

        $signature->signature_path = $path;
        $signature->title = $validated['title'] ?: ($signature->title ?: 'Head, Human Resource');
        $signature->save();

        return redirect()->route('settings.index', ['tab' => 'signatures'])
            ->with('success', "Signature drawn and saved for {$user->name}.");
    }

    public function updateSignatureTitle(Request $request, Signature $signature): RedirectResponse
    {
        Gate::authorize('manage-settings');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
        ]);

        $signature->update(['title' => $validated['title']]);

        return redirect()->route('settings.index', ['tab' => 'signatures'])
            ->with('success', 'Signatory title updated.');
    }

    public function markSignatory(Signature $signature): RedirectResponse
    {
        Gate::authorize('manage-settings');

        abort_unless($signature->signature_path, 422, 'Upload a signature image first.');

        $signature->markAsSignatory();

        return redirect()->route('settings.index', ['tab' => 'signatures'])
            ->with('success', "{$signature->user->name} is now the active signatory.");
    }

    public function clearSignatory(Signature $signature): RedirectResponse
    {
        Gate::authorize('manage-settings');

        $signature->update(['is_signatory' => false]);
        Signature::clearCache();

        return redirect()->route('settings.index', ['tab' => 'signatures'])
            ->with('success', 'Active signatory cleared.');
    }

    public function removeSignature(Signature $signature): RedirectResponse
    {
        Gate::authorize('manage-settings');

        if ($signature->signature_path && Storage::disk('public')->exists($signature->signature_path)) {
            Storage::disk('public')->delete($signature->signature_path);
        }

        $signature->delete();

        return redirect()->route('settings.index', ['tab' => 'signatures'])
            ->with('success', 'Signature removed.');
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
