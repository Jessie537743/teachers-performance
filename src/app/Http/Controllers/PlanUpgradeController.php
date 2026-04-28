<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanUpgradeController extends Controller
{
    public function show(Request $request): View
    {
        $feature = $request->query('feature');
        $currentPlan = plan()->plan();

        // Find plans that DO include this capability — show those as upgrade options
        $upgrades = collect(config('plans'))
            ->filter(fn ($plan) => $feature && ! empty($plan['capabilities'][$feature]))
            ->values()
            ->all();

        return view('plan.upgrade', [
            'feature'     => $feature,
            'currentPlan' => $currentPlan,
            'upgrades'    => $upgrades,
        ]);
    }
}
