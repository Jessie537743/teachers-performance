<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\AuditLog;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Reject deactivated accounts immediately
        if (!$user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['login' => 'Your account has been deactivated. Please contact the administrator.']);
        }

        $request->session()->regenerate();

        try { AuditLog::logAuth('login', $user, 'Login successful'); } catch (\Throwable $e) { /* table may not exist yet */ }

        // Send to change-password screen if required
        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->to($this->redirectByRole($user->primaryRole()));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        Auth::guard('web')->logout();

        try { AuditLog::logAuth('logout', $user, 'Logout'); } catch (\Throwable $e) { /* table may not exist yet */ }

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Resolve the post-login redirect destination based on the user's role.
     */
    private function redirectByRole(string $role): string
    {
        return route('dashboard');
    }
}
