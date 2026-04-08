<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    /**
     * Show the change-password form.
     */
    public function show(): View
    {
        return view('auth.change-password');
    }

    /**
     * Validate and apply the new password.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $user->password             = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        return redirect()->to($this->redirectByRole($user->role))
            ->with('status', 'Password changed successfully.');
    }

    /**
     * Resolve the post-change redirect destination by role.
     */
    private function redirectByRole(string $role): string
    {
        return route('dashboard');
    }
}
