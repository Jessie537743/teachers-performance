<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Cache key namespaced by tenant so values don't leak across tenants
     * (CacheTenancyBootstrapper is intentionally omitted in config/tenancy.php).
     */
    protected static function cacheKey(string $key): string
    {
        $scope = function_exists('tenant') && tenant() ? tenant()->getTenantKey() : 'central';
        return "setting:{$scope}:{$key}";
    }

    /**
     * Get a setting value by key, with optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(static::cacheKey($key), 300, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(static::cacheKey($key));
    }

    /**
     * Clear all settings cache (current tenant scope only).
     */
    public static function clearCache(): void
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget(static::cacheKey($key));
        }
    }
}
