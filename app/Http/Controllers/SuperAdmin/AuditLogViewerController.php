<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AuditLogViewerController extends Controller
{
    /**
     * Display searchable, immutable audit log records for Super Admin.
     */
    public function index(Request $request): View
    {
        $logs = collect([]);
        $actions = [];
        $roles = [];

        if (Schema::hasTable('audit_logs')) {
            $query = AuditLog::with('actor')->latest('id');

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                      ->orWhere('actor_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('ip_address', 'like', "%{$search}%")
                      ->orWhere('target_type', 'like', "%{$search}%");
                });
            }

            if ($request->filled('action')) {
                $query->where('action', $request->input('action'));
            }

            if ($request->filled('actor_role')) {
                $query->where('actor_role', $request->input('actor_role'));
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

            $logs = $query->paginate(25)->withQueryString();

            $actions = AuditLog::distinct()->pluck('action')->filter()->values()->all();
            $roles = AuditLog::distinct()->pluck('actor_role')->filter()->values()->all();
        }

        return view('super-admin.audit-logs.index', compact('logs', 'actions', 'roles'));
    }
}
