<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiIntegration;
use App\Services\ApiIntegrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiIntegrationController extends Controller
{
    public function __construct(private ApiIntegrationService $service)
    {
        $this->middleware('can:manage-integrations');
    }

    public function index(): View
    {
        $integration = ApiIntegration::query()->first();
        return view('admin.api-integration.index', compact('integration'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'base_url'            => ['required', 'url', 'max:512'],
            'auth_mode'           => ['required', 'string', 'in:' . implode(',', array_keys(ApiIntegration::authModes()))],
            'api_key'             => ['nullable', 'string', 'max:512'],
            'api_secret'          => ['nullable', 'string', 'max:512'],
            'header_name'         => ['nullable', 'string', 'max:64'],
            'header_prefix'       => ['nullable', 'string', 'max:32'],
            'secret_header_name'  => ['nullable', 'string', 'max:64'],
            'is_active'           => ['sometimes', 'boolean'],
            'paths'               => ['array'],
            'paths.students'      => ['nullable', 'string', 'max:255'],
            'paths.departments'   => ['nullable', 'string', 'max:255'],
            'paths.subjects'      => ['nullable', 'string', 'max:255'],
            'paths.courses'       => ['nullable', 'string', 'max:255'],
        ]);

        $integration = ApiIntegration::query()->first();

        // Preserve existing credentials on update if the user left the inputs
        // blank (mask-style UX — don't force re-entry on every save).
        $apiKey    = $data['api_key']    ?: ($integration?->api_key    ?? '');
        $apiSecret = $data['api_secret'] ?: ($integration?->api_secret ?? null);

        // Mode-specific defaults for header names so the UI shows sensible
        // values even when an admin doesn't touch them.
        $mode = $data['auth_mode'];
        $defaultHeaderName = match ($mode) {
            ApiIntegration::AUTH_KEY_AND_SECRET => 'X-API-Key',
            ApiIntegration::AUTH_BASIC          => 'Authorization', // unused, but kept consistent
            default                              => 'Authorization',
        };
        $defaultHeaderPrefix = $mode === ApiIntegration::AUTH_API_KEY ? 'Bearer ' : '';
        $defaultSecretHeader = $mode === ApiIntegration::AUTH_KEY_AND_SECRET ? 'X-API-Secret' : null;

        $payload = [
            'name'               => $data['name'],
            'base_url'           => $data['base_url'],
            'auth_mode'          => $mode,
            'api_key'            => $apiKey,
            'api_secret'         => $apiSecret,
            'header_name'        => $data['header_name']        ?: $defaultHeaderName,
            'header_prefix'      => $data['header_prefix']      ?? $defaultHeaderPrefix,
            'secret_header_name' => $data['secret_header_name'] ?: $defaultSecretHeader,
            'resource_paths' => array_filter([
                'students'    => $data['paths']['students']    ?? null,
                'departments' => $data['paths']['departments'] ?? null,
                'subjects'    => $data['paths']['subjects']    ?? null,
                'courses'     => $data['paths']['courses']     ?? null,
            ]),
            'is_active'      => (bool) ($data['is_active'] ?? false),
        ];

        if ($integration) {
            $integration->fill($payload)->save();
        } else {
            ApiIntegration::create($payload);
        }

        return back()->with('success', 'API integration settings saved.');
    }

    public function test(): RedirectResponse
    {
        $integration = ApiIntegration::query()->first();
        if (!$integration) {
            return back()->with('error', 'Save the integration first before testing.');
        }

        $result = $this->service->testConnection($integration);

        return back()->with($result['ok'] ? 'success' : 'error',
            $result['ok']
                ? 'Connection successful: ' . $result['message']
                : 'Connection failed: ' . $result['message']
        );
    }

    public function sync(Request $request, string $resource): RedirectResponse
    {
        if (!in_array($resource, ApiIntegration::supportedResources(), true)) {
            return back()->with('error', "Unknown resource: {$resource}");
        }

        $integration = ApiIntegration::query()->first();
        if (!$integration) {
            return back()->with('error', 'Save the integration first before syncing.');
        }
        if (!$integration->is_active) {
            return back()->with('error', 'Activate the integration first (toggle Active on).');
        }

        $result = $this->service->sync($integration, $resource);

        if ($result['status'] === 'error') {
            return back()->with('error', "Sync failed for {$resource}: " . ($result['error'] ?? 'unknown error'));
        }

        $s = $result['stats'];
        return back()->with('success', sprintf(
            'Synced %s — %d created, %d updated, %d skipped, %d errors (of %d total).',
            $resource, $s['created'] ?? 0, $s['updated'] ?? 0, $s['skipped'] ?? 0, $s['errors'] ?? 0, $s['total'] ?? 0
        ));
    }

    public function destroy(): RedirectResponse
    {
        ApiIntegration::query()->delete();
        return back()->with('success', 'API integration removed.');
    }
}
