<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic observer that automatically logs created/updated/deleted events
 * for any Eloquent model it is attached to.
 */
class AuditObserver
{
    /**
     * Attributes to never include in audit logs (sensitive data).
     */
    private const HIDDEN_ATTRIBUTES = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function created(Model $model): void
    {
        try {
            $values = $this->filterAttributes($model->getAttributes());

            AuditLog::log(
                action: 'created',
                description: class_basename($model) . ' created' . $this->modelLabel($model),
                model: $model,
                newValues: $values
            );
        } catch (\Throwable $e) {
            // Silently fail if audit_logs table doesn't exist yet
        }
    }

    public function updated(Model $model): void
    {
        $changed = $model->getChanges();
        $original = collect($model->getOriginal())
            ->only(array_keys($changed))
            ->all();

        $changed = $this->filterAttributes($changed);
        $original = $this->filterAttributes($original);

        if (empty($changed)) {
            return;
        }

        // Detect deactivate/reactivate pattern
        $action = 'updated';
        if (array_key_exists('is_active', $changed)) {
            $action = $changed['is_active'] ? 'reactivated' : 'deactivated';
        }

        try {
            AuditLog::log(
                action: $action,
                description: class_basename($model) . ' ' . $action . $this->modelLabel($model),
                model: $model,
                oldValues: $original,
                newValues: $changed
            );
        } catch (\Throwable $e) {
            // Silently fail if audit_logs table doesn't exist yet
        }
    }

    public function deleted(Model $model): void
    {
        try {
            $values = $this->filterAttributes($model->getAttributes());

            AuditLog::log(
                action: 'deleted',
                description: class_basename($model) . ' deleted' . $this->modelLabel($model),
                model: $model,
                oldValues: $values
            );
        } catch (\Throwable $e) {
            // Silently fail if audit_logs table doesn't exist yet
        }
    }

    /**
     * Remove sensitive attributes from audit data.
     */
    private function filterAttributes(array $attributes): array
    {
        return collect($attributes)
            ->except(self::HIDDEN_ATTRIBUTES)
            ->all();
    }

    /**
     * Build a human-readable label for the model instance.
     */
    private function modelLabel(Model $model): string
    {
        if (method_exists($model, 'getAttribute')) {
            if ($name = $model->getAttribute('name')) {
                return ": {$name}";
            }
            if ($word = $model->getAttribute('word')) {
                return ": {$word}";
            }
            if ($title = $model->getAttribute('title')) {
                return ": {$title}";
            }
            if ($code = $model->getAttribute('code')) {
                return ": {$code}";
            }
            if ($key = $model->getAttribute('key')) {
                return ": {$key}";
            }
        }

        return ' #' . $model->getKey();
    }
}
