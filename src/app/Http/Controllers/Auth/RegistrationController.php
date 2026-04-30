<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationSubmittedMail;
use App\Models\Department;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function show(Request $request): View
    {
        $kind = in_array($request->query('kind'), ['student', 'personnel'], true)
            ? $request->query('kind')
            : null;

        return view('auth.register', [
            'kind'        => $kind,
            'departments' => Department::query()->orderBy('name')->get(),
            'roles'       => $this->personnelRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $kind = $request->input('kind');
        abort_unless(in_array($kind, ['student', 'personnel'], true), 422);

        $rules = [
            'kind'                  => ['required', 'in:student,personnel'],
            'name'                  => ['required', 'string', 'max:191'],
            'email'                 => [
                'required', 'email', 'max:191',
                Rule::unique('users', 'email'),
                Rule::unique('registration_requests', 'email')->where('status', 'pending'),
            ],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'department_id'         => ['required', 'integer', Rule::exists('departments', 'id')],
        ];

        if ($kind === 'student') {
            $rules += [
                'course'         => ['required', 'string', 'max:120'],
                'year_level'     => ['required', 'string', 'max:50'],
                'section'        => ['required', 'string', 'max:50'],
                'student_status' => ['required', Rule::in(['regular', 'irregular'])],
                'school_year'    => ['required', 'string', 'max:50'],
                'semester'       => ['required', 'string', 'max:30'],
            ];
        } else {
            $rules += [
                'role'                => ['required', Rule::in($this->personnelRoles())],
                'department_position' => ['required', 'string', 'max:40'],
            ];
        }

        $data = $request->validate($rules);

        $payload = collect($data)
            ->except(['name', 'email', 'password', 'password_confirmation', 'department_id', 'kind'])
            ->all();

        $req = RegistrationRequest::create([
            'kind'          => $kind,
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'department_id' => $data['department_id'],
            'payload'       => $payload,
            'status'        => 'pending',
        ]);

        try {
            Mail::to($req->email)->queue(new RegistrationSubmittedMail($req));
        } catch (\Throwable $e) {
            // Don't fail the submission if mail driver hiccups; submission is still recorded.
            report($e);
        }

        return redirect()->route('register.submitted', ['email' => $req->email]);
    }

    public function submitted(Request $request): View
    {
        return view('auth.register-submitted', [
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Personnel roles selectable on the public form. Excludes 'student' and
     * 'admin' (admin is bootstrapped via tenant activation, not self-registered).
     */
    private function personnelRoles(): array
    {
        return ['faculty', 'dean', 'head', 'human_resource', 'staff'];
    }
}
