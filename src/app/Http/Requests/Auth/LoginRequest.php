<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim((string) $this->input('login'));
        $password = (string) $this->input('password');
        $remember = $this->boolean('remember');

        $attempted = false;
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $attempted = Auth::attempt(['email' => $login, 'password' => $password], $remember);
        } else {
            // Student-only username login (student_id).
            $query = User::query()->where('username', $login);
            if (Schema::hasColumn((new User)->getTable(), 'roles')) {
                $user = $query->whereJsonContains('roles', 'student')->first();
            } else {
                // Legacy schema: single `role` enum before roles JSON migration
                $user = $query->where('role', 'student')->first();
            }
            if ($user && Hash::check($password, $user->password)) {
                Auth::login($user, $remember);
                $attempted = true;
            }
        }

        // Check for pending password reset request
        if ($attempted) {
            try {
                $authedUser = Auth::user();
                $pendingReset = \App\Models\PasswordResetRequest::where('user_id', $authedUser->id)->pending()->latest('created_at')->first();
                if ($pendingReset) {
                    Auth::logout();
                    throw ValidationException::withMessages([
                        'login' => 'Your password reset request is pending admin approval. Please wait.',
                    ]);
                }
            } catch (ValidationException $e) {
                throw $e;
            } catch (\Throwable $e) {
                // Table may not exist yet — skip check gracefully
            }
        }

        if (! $attempted) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
