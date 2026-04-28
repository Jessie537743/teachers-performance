<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivationCode;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(Request $request): View
    {
        $plans = config('plans');

        $counts = Tenant::where('status', 'active')
            ->selectRaw('plan, COUNT(*) as count')
            ->groupBy('plan')
            ->pluck('count', 'plan')
            ->all();

        $statusFilter = $request->query('status', 'all');
        $codesQuery = ActivationCode::with('tenant')->orderByDesc('created_at');

        if ($statusFilter === 'all') {
            // hide redeemed by default to focus on actionable rows
            $codesQuery->whereIn('status', ['unredeemed', 'revoked', 'expired']);
        } elseif (in_array($statusFilter, ['unredeemed', 'redeemed', 'revoked', 'expired'], true)) {
            $codesQuery->where('status', $statusFilter);
        }

        $codes = $codesQuery->limit(50)->get();

        return view('super-admin.plans.index', [
            'plans'        => $plans,
            'counts'       => $counts,
            'codes'        => $codes,
            'statusFilter' => $statusFilter,
        ]);
    }
}
