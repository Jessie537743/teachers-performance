<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PasswordResetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PasswordResetApprovalController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-settings');

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

        return back()->with('success', "Password reset request declined.");
    }
}
