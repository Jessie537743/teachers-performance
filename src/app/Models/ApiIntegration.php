<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-tenant API integration configuration.
 *
 * We intentionally keep this to a single row per tenant for the first version
 * (the controller's index() loads `first()` and `update()` uses
 * `updateOrCreate`). Supporting multiple integrations is a future-tense
 * change — drop a `name` selector into the UI and treat this as a regular
 * resource controller.
 */
class ApiIntegration extends Model
{
    protected $fillable = [
        'name',
        'base_url',
        'auth_mode',
        'api_key',
        'api_secret',
        'header_name',
        'header_prefix',
        'secret_header_name',
        'resource_paths',
        'is_active',
        'last_synced_at',
        'last_sync_resource',
        'last_sync_status',
        'last_sync_error',
        'last_sync_stats',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'last_synced_at'   => 'datetime',
        'resource_paths'   => 'array',
        'last_sync_stats'  => 'array',
        // Encrypt credentials at rest — admins only ever see masked inputs
        // in the UI, never the plaintext after first save.
        'api_key'          => 'encrypted',
        'api_secret'       => 'encrypted',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    public const AUTH_API_KEY        = 'api_key';
    public const AUTH_KEY_AND_SECRET = 'key_and_secret';
    public const AUTH_BASIC          = 'basic';

    /** Human-readable labels for the auth-mode dropdown. */
    public static function authModes(): array
    {
        return [
            self::AUTH_API_KEY        => 'API Key (single header)',
            self::AUTH_KEY_AND_SECRET => 'Key + Secret (two headers)',
            self::AUTH_BASIC          => 'HTTP Basic Auth',
        ];
    }

    /**
     * Resolve the full URL for a resource ("students" | "departments" | "subjects" | "courses").
     * Returns null when the integration has no path configured for that resource.
     */
    public function urlFor(string $resource): ?string
    {
        $paths = $this->resource_paths ?? [];
        $path  = $paths[$resource] ?? null;
        if (!$path) {
            return null;
        }
        return rtrim($this->base_url, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Build the HTTP header map that should accompany every request to the
     * external API. The shape depends on `auth_mode`:
     *
     *   api_key          → [ header_name => header_prefix + api_key ]
     *                      e.g. ["Authorization" => "Bearer abc123"]
     *
     *   key_and_secret   → [ header_name => api_key,
     *                        secret_header_name => api_secret ]
     *                      e.g. ["X-API-Key" => "abc", "X-API-Secret" => "xyz"]
     *
     *   basic            → [ "Authorization" => "Basic " . base64(key:secret) ]
     */
    public function buildAuthHeaders(): array
    {
        $mode = $this->auth_mode ?: self::AUTH_API_KEY;

        switch ($mode) {
            case self::AUTH_KEY_AND_SECRET:
                $keyHeader    = $this->header_name        ?: 'X-API-Key';
                $secretHeader = $this->secret_header_name ?: 'X-API-Secret';
                return [
                    $keyHeader    => (string) $this->api_key,
                    $secretHeader => (string) $this->api_secret,
                ];

            case self::AUTH_BASIC:
                $userPass = ((string) $this->api_key) . ':' . ((string) $this->api_secret);
                return ['Authorization' => 'Basic ' . base64_encode($userPass)];

            case self::AUTH_API_KEY:
            default:
                $prefix     = trim($this->header_prefix ?? '');
                $headerVal  = $prefix === ''
                    ? (string) $this->api_key
                    : $prefix . ' ' . $this->api_key;
                $headerName = $this->header_name ?: 'Authorization';
                return [$headerName => $headerVal];
        }
    }

    /**
     * Resources the integration is allowed to sync, in display order.
     */
    public static function supportedResources(): array
    {
        return ['students', 'departments', 'subjects', 'courses'];
    }
}
