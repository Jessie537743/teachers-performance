<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        Gate::authorize('manage-settings');

        try {
            AuditLog::query()->exists();
        } catch (\Throwable $e) {
            return redirect()->route('dashboard')->with('error', 'Audit logs table is not available yet. Please run migrations.');
        }

        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($action = $request->input('action')) {
            $query->forAction($action);
        }
        if ($modelType = $request->input('model_type')) {
            $query->forModel($modelType);
        }
        if ($from = $request->input('from')) {
            $query->where('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $logs = $query->paginate(30)->withQueryString();

        $stats = [
            'total' => AuditLog::count(),
            'today' => AuditLog::whereDate('created_at', today())->count(),
        ];

        $actions = ['created', 'updated', 'deleted', 'deactivated', 'reactivated', 'login', 'logout', 'submitted', 'trained', 'delegated', 'revoked'];
        $modelTypes = AuditLog::whereNotNull('model_type')->distinct()->pluck('model_type')->sort()->values();

        return view('admin.audit-logs', compact('logs', 'stats', 'actions', 'modelTypes'));
    }
}
