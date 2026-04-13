<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $user->load(['department', 'facultyProfile.department', 'studentProfile']);

        return view('account.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users')->ignore($user->id)],
        ];

        if ($user->facultyProfile && in_array($user->role, ['faculty', 'dean', 'head'], true)) {
            $rules['account_comment'] = ['nullable', 'string', 'max:2000'];
        }

        $validated = $request->validate($rules);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->facultyProfile && in_array($user->role, ['faculty', 'dean', 'head'], true)) {
            $user->facultyProfile->update([
                'account_comment' => $validated['account_comment'] ?? null,
            ]);
        }

        return back()->with('success', 'Account details updated successfully.');
    }
}
