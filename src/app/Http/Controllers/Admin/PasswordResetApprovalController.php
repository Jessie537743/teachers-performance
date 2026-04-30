<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetApprovedMail;
use App\Mail\PasswordResetDeclinedMail;
use App\Models\AuditLog;
use App\Models\PasswordResetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class PasswordResetApprovalController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        Gate::authorize('manage-settings');

        try {
            PasswordResetRequest::query()->exists();
        } catch (\Throwable $e) {
            return redirect()->route('dashboard')->with('error', 'Password reset requests table is not available yet. Please run migrations.');
        }

        $query = PasswordResetRequest::with(['user', 'reviewer'])->orderByDesc('created_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->input('search')) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        $requests = $query->paginate(25)->withQueryString();

        $pendingCount = PasswordResetRequest::pending()->count();

        return view('admin.password-reset-requests', compact('requests', 'pendingCount'));
    }

    public function approve(PasswordResetRequest $passwordResetRequest): RedirectResponse
    {
        Gate::authorize('manage-settings');

        if (!$passwordResetRequest->isPending()) {
            return back()->with('error', 'This request has already been processed.');
        }

        $user = $passwordResetRequest->user;
        $user->forceFill(['password' => $passwordResetRequest->new_password_hash])->save();

        $passwordResetRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        AuditLog::log('updated', "Password reset approved for {$user->name} ({$user->email})", $user);

        try {
            $loginUrl = route('login');
            Mail::to($user->email)->queue(
                new PasswordResetApprovedMail($passwordResetRequest->fresh('user'), $loginUrl)
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', "Password reset approved for {$user->name}.");
    }

    public function decline(Request $request, PasswordResetRequest $passwordResetRequest): RedirectResponse
    {
        Gate::authorize('manage-settings');

        if (!$passwordResetRequest->isPending()) {
            return back()->with('error', 'This request has already been processed.');
        }

        $passwordResetRequest->update([
            'status' => 'declined',
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $user = $passwordResetRequest->user;
        AuditLog::log('updated', "Password reset declined for {$user->name} ({$user->email})", $user);

        try {
            Mail::to($user->email)->queue(
                new PasswordResetDeclinedMail($passwordResetRequest->fresh('user'))
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', "Password reset request declined.");
    }
}
