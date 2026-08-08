<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AuditLogViewerController extends Controller
{
    /**
     * Display searchable, immutable audit log records.
     */
    public function index(Request $request): View
    {
        $logs = collect([]);

        if (Schema::hasTable('audit_logs')) {
            $query = DB::table('audit_logs')->latest('created_at');

            if ($request->filled('action')) {
                $query->where('action', 'like', '%' . $request->action . '%');
            }

            if ($request->filled('actor')) {
                $query->where('actor_name', 'like', '%' . $request->actor . '%');
            }

            $logs = $query->paginate(20);
        }

        return view('super-admin.audit-logs.index', compact('logs'));
    }
}
