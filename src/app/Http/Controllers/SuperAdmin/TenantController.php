<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::orderByDesc('id')->get();

        return view('super-admin.tenants.index', ['tenants' => $tenants]);
    }
}
