<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_roles',
        'action',
        'model_type',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // Static helpers
    // -------------------------------------------------------------------------

    /**
     * Log an audit event.
     */
    public static function log(
        string $action,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): ?self {
        $user = Auth::user();

        try {
            return static::create([
                'user_id'    => $user?->id,
                'user_name'  => $user?->name ?? 'System',
                'user_roles' => $user ? implode(', ', $user->roles ?? []) : null,
                'action'     => $action,
                'model_type' => $model ? class_basename($model) : null,
                'model_id'   => $model?->getKey(),
                'description'=> $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => Request::ip(),
                'user_agent' => substr(Request::userAgent() ?? '', 0, 255),
                'created_at' => now(),
            ]);
        } catch (QueryException $e) {
            if (self::isMissingAuditTable($e)) {
                Log::debug('audit_logs skipped (table missing); run php artisan migrate');

                return null;
            }
            throw $e;
        }
    }

    /**
     * Log an authentication event (login/logout).
     */
    public static function logAuth(string $action, User $user, string $description = ''): ?self
    {
        try {
            return static::create([
                'user_id'    => $user->id,
                'user_name'  => $user->name,
                'user_roles' => implode(', ', $user->roles ?? []),
                'action'     => $action,
                'model_type' => 'User',
                'model_id'   => $user->id,
                'description'=> $description ?: ucfirst($action) . ' successful',
                'ip_address' => Request::ip(),
                'user_agent' => substr(Request::userAgent() ?? '', 0, 255),
                'created_at' => now(),
            ]);
        } catch (QueryException $e) {
            if (self::isMissingAuditTable($e)) {
                Log::debug('audit_logs skipped (table missing); run php artisan migrate');

                return null;
            }
            throw $e;
        }
    }

    private static function isMissingAuditTable(QueryException $e): bool
    {
        $msg = $e->getMessage();

        return str_contains($msg, 'audit_logs')
            && (str_contains($msg, "doesn't exist") || str_contains($msg, 'Base table or view not found'));
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeInDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        return $query;
    }

    // -------------------------------------------------------------------------
    // Display helpers
    // -------------------------------------------------------------------------

    public function actionBadgeClass(): string
    {
        return match ($this->action) {
            'created'      => 'bg-green-100 text-green-700',
            'updated'      => 'bg-blue-100 text-blue-700',
            'deleted', 'deactivated' => 'bg-red-100 text-red-700',
            'reactivated'  => 'bg-emerald-100 text-emerald-700',
            'login'        => 'bg-teal-100 text-teal-700',
            'logout'       => 'bg-gray-100 text-gray-600',
            'submitted'    => 'bg-purple-100 text-purple-700',
            'trained'      => 'bg-indigo-100 text-indigo-700',
            'delegated'    => 'bg-amber-100 text-amber-700',
            'revoked'      => 'bg-orange-100 text-orange-700',
            default        => 'bg-gray-100 text-gray-700',
        };
    }
}
