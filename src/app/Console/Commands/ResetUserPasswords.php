<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ResetUserPasswords extends Command
{
    /**
     * Reset passwords for groups of users to a known default.
     *
     * Examples:
     *   php artisan users:reset-defaults
     *   php artisan users:reset-defaults --role=student --password=student123
     *   php artisan users:reset-defaults --role=admin --email=admin@sample.com --force
     *   php artisan users:reset-defaults --all --force --no-must-change
     */
    protected $signature = 'users:reset-defaults
        {--role=*           : One or more roles to target (admin, hr, dean, head, faculty, student). Repeatable.}
        {--email=           : Reset only the user with this exact email address.}
        {--password=        : New plaintext password. Defaults to a per-role value.}
        {--all              : Reset every user in the system. Mutually exclusive with --role/--email.}
        {--no-must-change   : Do NOT mark accounts as must_change_password.}
        {--force            : Skip the interactive confirmation.}';

    protected $description = 'Bulk-reset user passwords to a known default. Marks affected accounts must_change_password by default.';

    private array $defaultByRole = [
        'admin'   => 'admin123',
        'hr'      => 'hr123',
        'dean'    => 'dean123',
        'head'    => 'head123',
        'faculty' => 'faculty123',
        'student' => 'student123',
    ];

    public function handle(): int
    {
        $email    = $this->option('email');
        $roles    = (array) $this->option('role');
        $all      = (bool) $this->option('all');
        $password = $this->option('password');
        $force    = (bool) $this->option('force');
        $markMustChange = ! $this->option('no-must-change');
        $hasMustChange  = Schema::hasColumn('users', 'must_change_password');

        // Validate target selection
        $targetCount = (int) $all + (int) ($email !== null) + (int) (! empty($roles));
        if ($targetCount === 0) {
            $this->error('Specify at least one of --all, --role, or --email.');
            $this->line('Examples:');
            $this->line('  php artisan users:reset-defaults --role=student');
            $this->line('  php artisan users:reset-defaults --email=admin@sample.com');
            $this->line('  php artisan users:reset-defaults --all --force');
            return self::FAILURE;
        }
        if ($all && ($email !== null || ! empty($roles))) {
            $this->error('--all cannot be combined with --role or --email.');
            return self::FAILURE;
        }

        // Build the query
        $query = User::query();
        if ($email !== null) {
            $query->where('email', $email);
        } elseif (! empty($roles)) {
            $query->whereIn('role', $roles);
        }

        $count = (clone $query)->count();
        if ($count === 0) {
            $this->warn('No users matched the given criteria.');
            return self::SUCCESS;
        }

        // Single shared password (either explicit or for the single-role / email case)
        $usingPerRoleDefaults = ($password === null) && ! $email && (empty($roles) || count($roles) > 1);

        $this->info("About to reset passwords for {$count} user(s).");
        if ($email)            $this->line("  email = {$email}");
        if (! empty($roles))   $this->line('  roles = ' . implode(', ', $roles));
        if ($all)              $this->line('  scope = ALL USERS');
        if ($usingPerRoleDefaults) {
            $this->line('  password = per-role defaults (admin123, faculty123, student123, ...)');
        } else {
            $effective = $password ?? ($email ? 'per-role default' : ($this->defaultByRole[$roles[0] ?? ''] ?? 'changeme'));
            $this->line("  password = {$effective}");
        }
        if ($hasMustChange) {
            $this->line('  must_change_password = ' . ($markMustChange ? 'true' : 'false'));
        }

        if (! $force && ! $this->confirm('Proceed?', false)) {
            $this->warn('Aborted.');
            return self::SUCCESS;
        }

        $updated = 0;
        $query->chunkById(200, function ($users) use ($password, $usingPerRoleDefaults, $markMustChange, $hasMustChange, &$updated) {
            foreach ($users as $user) {
                $plain = $password
                    ?? ($usingPerRoleDefaults
                        ? ($this->defaultByRole[$user->role] ?? 'changeme')
                        : ($this->defaultByRole[$user->role] ?? 'changeme'));

                $user->password = Hash::make($plain);
                if ($hasMustChange) {
                    $user->must_change_password = $markMustChange;
                }
                $user->save();
                $updated++;
            }
        });

        $this->info("Reset {$updated} password(s).");
        if ($hasMustChange && $markMustChange) {
            $this->line('Affected users will be required to change their password on next login.');
        }

        return self::SUCCESS;
    }
}
