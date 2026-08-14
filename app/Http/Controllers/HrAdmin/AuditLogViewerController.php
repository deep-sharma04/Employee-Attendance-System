<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AuditLogViewerController extends Controller
{
    /**
     * Display a limited, operational audit log view for HR Admin.
     */
    public function index(Request $request): View
    {
        $logs = collect([]);
        $actions = [];

        if (Schema::hasTable('audit_logs')) {
            $query = AuditLog::with('actor')
                ->operationalOnly()
                ->latest('id');

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                      ->orWhere('actor_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('target_type', 'like', "%{$search}%");
                });
            }

            if ($request->filled('action')) {
                $query->where('action', $request->input('action'));
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

            $logs = $query->paginate(25)->withQueryString();

            $actions = AuditLog::operationalOnly()->distinct()->pluck('action')->filter()->values()->all();
        }

        return view('hr-admin.audit-logs.index', compact('logs', 'actions'));
    }
}
