<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationDecidedMail;
use App\Models\Department;
use App\Models\FacultyProfile;
use App\Models\RegistrationRequest;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Approval queue for self-service registrations.
 *
 *   Personnel requests   → admins / HR
 *   Student requests     → deans (scoped to their department)
 */
class RegistrationApprovalController extends Controller
{
    public function index(Request $request): View
    {
        [$kind, $scopeMessage] = $this->resolveScope($request);

        $query = RegistrationRequest::query()
            ->with(['department', 'decider'])
            ->where('kind', $kind)
            ->orderByDesc('id');

        // Deans only see their own department's student requests.
        $user = $request->user();
        if ($kind === 'student' && $user->hasRole(['dean', 'head']) && ! $user->hasRole(['admin', 'human_resource'])) {
            $query->where('department_id', $user->department_id);
        }

        $statusFilter = $request->query('status', 'pending');
        if (in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $statusFilter);
        }

        $requests = $query->paginate(20)->withQueryString();

        $counts = RegistrationRequest::query()
            ->where('kind', $kind)
            ->when(
                $kind === 'student' && $user->hasRole(['dean', 'head']) && ! $user->hasRole(['admin', 'human_resource']),
                fn ($q) => $q->where('department_id', $user->department_id),
            )
            ->selectRaw('status, COUNT(*) c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        return view('admin.registration-approvals.index', [
            'requests'     => $requests,
            'kind'         => $kind,
            'statusFilter' => $statusFilter,
            'counts'       => $counts,
            'scopeMessage' => $scopeMessage,
        ]);
    }

    public function approve(Request $request, RegistrationRequest $registration): RedirectResponse
    {
        $this->authorizeAction($request, $registration);
        abort_unless($registration->status === 'pending', 422);

        $user = DB::transaction(function () use ($request, $registration) {
            // Re-check email isn't already taken (race-condition safe)
            if (User::where('email', $registration->email)->exists()) {
                abort(409, 'Email is already in use by an existing account.');
            }

            $userRole = $registration->kind === 'student'
                ? 'student'
                : ($registration->payload['role'] ?? 'staff');

            /** @var User $u */
            $u = User::create([
                'name'                 => $registration->name,
                'email'                => $registration->email,
                'password'             => $registration->password_hash,    // already hashed
                'roles'                => [$userRole],
                'is_active'            => true,
                'must_change_password' => false,
                'department_id'        => $registration->department_id,
            ]);

            // Hashed password was set raw; ensure model doesn't double-hash on save
            DB::table('users')->where('id', $u->id)->update(['password' => $registration->password_hash]);

            if ($registration->kind === 'student') {
                StudentProfile::create([
                    'user_id'        => $u->id,
                    'department_id'  => $registration->department_id,
                    'course'         => $registration->payload['course'] ?? '',
                    'year_level'     => $registration->payload['year_level'] ?? '',
                    'section'        => $registration->payload['section'] ?? '',
                    'student_status' => $registration->payload['student_status'] ?? 'regular',
                    'school_year'    => $registration->payload['school_year'] ?? '',
                    'semester'       => $registration->payload['semester'] ?? '',
                ]);
            } else {
                FacultyProfile::create([
                    'user_id'             => $u->id,
                    'department_id'       => $registration->department_id,
                    'department_position' => $registration->payload['department_position'] ?? 'faculty',
                ]);
            }

            $registration->update([
                'status'     => 'approved',
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
                'reason'     => null,
            ]);

            return $u;
        });

        try {
            Mail::to($registration->email)->send(
                new RegistrationDecidedMail($registration->fresh(), $this->loginUrl())
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', "Registration approved. Welcome email sent to {$registration->email}.");
    }

    public function reject(Request $request, RegistrationRequest $registration): RedirectResponse
    {
        $this->authorizeAction($request, $registration);
        abort_unless($registration->status === 'pending', 422);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $registration->update([
            'status'     => 'rejected',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'reason'     => $data['reason'],
        ]);

        try {
            Mail::to($registration->email)->send(
                new RegistrationDecidedMail($registration->fresh(), $this->loginUrl())
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', "Registration rejected. {$registration->email} has been notified.");
    }

    private function resolveScope(Request $request): array
    {
        $user = $request->user();
        $forced = $request->query('kind');

        // Admin & HR can switch between queues
        if ($user->hasRole(['admin', 'human_resource'])) {
            $kind = in_array($forced, ['student', 'personnel'], true) ? $forced : 'personnel';
            $msg  = $kind === 'student'
                ? 'Reviewing student registrations across all departments.'
                : 'Reviewing personnel registrations.';
            return [$kind, $msg];
        }

        // Deans / heads only ever see student queue, scoped to their department
        if ($user->hasRole(['dean', 'head'])) {
            return ['student', 'Reviewing student registrations for your department.'];
        }

        abort(403, 'You are not authorized to view registration approvals.');
    }

    private function authorizeAction(Request $request, RegistrationRequest $reg): void
    {
        $user = $request->user();

        if ($user->hasRole(['admin', 'human_resource'])) {
            return;
        }

        if ($user->hasRole(['dean', 'head']) && $reg->kind === 'student' && (int) $reg->department_id === (int) $user->department_id) {
            return;
        }

        abort(403);
    }

    private function loginUrl(): string
    {
        return url('/login');
    }
}
