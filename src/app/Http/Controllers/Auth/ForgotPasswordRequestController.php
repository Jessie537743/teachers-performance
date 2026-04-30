<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetRequestSubmittedMail;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ForgotPasswordRequestController extends Controller
{
    public function showForm(): View
    {
        return view('auth.forgot-password-request');
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'date_of_birth' => ['required', 'date'],
            'course' => ['nullable', 'string'],
            'year_level' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !$user->date_of_birth || $user->date_of_birth->format('Y-m-d') !== $data['date_of_birth']) {
            return back()->withInput()->with('error', 'The information provided does not match our records.');
        }

        // If user is a student, also verify course and year_level
        if ($user->hasRole(['student'])) {
            $profile = $user->studentProfile;
            if (!$profile) {
                return back()->withInput()->with('error', 'Student profile not found.');
            }

            $courseMatch = $data['course'] && mb_strtolower(trim($data['course'])) === mb_strtolower(trim($profile->course ?? ''));
            $yearMatch = $data['year_level'] && trim($data['year_level']) === trim($profile->year_level ?? '');

            if (!$courseMatch || !$yearMatch) {
                return back()->withInput()->with('error', 'The information provided does not match our records.');
            }
        }

        // Check for existing pending request
        $existingPending = PasswordResetRequest::where('user_id', $user->id)->pending()->first();
        if ($existingPending) {
            return back()->with('error', 'You already have a pending password reset request. Please wait for admin approval.');
        }

        // Verification passed - show password form
        return view('auth.forgot-password-request', [
            'verified' => true,
            'verified_user_id' => $user->id,
            'verified_email' => $user->email,
        ]);
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::findOrFail($data['user_id']);

        // Verify no pending request exists
        $existingPending = PasswordResetRequest::where('user_id', $user->id)->pending()->first();
        if ($existingPending) {
            return redirect()->route('forgot-password.form')->with('error', 'You already have a pending request.');
        }

        $resetRequest = PasswordResetRequest::create([
            'user_id' => $user->id,
            'new_password_hash' => Hash::make($data['password']),
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        try {
            Mail::to($user->email)->queue(
                new PasswordResetRequestSubmittedMail($resetRequest->fresh('user'))
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return view('auth.forgot-password-request', ['submitted' => true]);
    }
}
