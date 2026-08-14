<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\IpAllowlist\StoreIpAllowlistRequest;
use App\Http\Requests\IpAllowlist\UpdateIpAllowlistRequest;
use App\Models\OfficeIpAllowlist;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class IpAllowlistController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * Display a listing of office IP allowlists.
     */
    public function index(): View
    {
        $ipAllowlists = OfficeIpAllowlist::with('creator')->latest()->get();

        return view('hr-admin.ip-allowlists.index', [
            'ipAllowlists' => $ipAllowlists,
        ]);
    }

    /**
     * Store a newly created office IP in database.
     */
    public function store(StoreIpAllowlistRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = Auth::id();

        $ip = OfficeIpAllowlist::create($validated);

        $this->auditLogger->log(
            action: 'ip_allowlist.created',
            targetType: 'App\Models\OfficeIpAllowlist',
            targetId: $ip->id,
            afterValues: $ip->toArray(),
            description: "Added IP {$ip->ip_address} to approved office network allowlist."
        );

        return back()->with('success', "Office IP address {$ip->ip_address} added to allowlist.");
    }

    /**
     * Update the specified IP entry.
     */
    public function update(UpdateIpAllowlistRequest $request, $id): RedirectResponse
    {
        $ip = OfficeIpAllowlist::findOrFail($id);
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', $ip->is_active);

        $beforeValues = $ip->toArray();
        $ip->update($validated);

        $this->auditLogger->log(
            action: 'ip_allowlist.updated',
            targetType: 'App\Models\OfficeIpAllowlist',
            targetId: $ip->id,
            beforeValues: $beforeValues,
            afterValues: $ip->fresh()->toArray(),
            description: "Updated office IP allowlist entry {$ip->ip_address}."
        );

        return back()->with('success', "Office IP {$ip->ip_address} updated successfully.");
    }

    /**
     * Toggle active/inactive state of an IP allowlist entry.
     */
    public function toggleStatus($id): RedirectResponse
    {
        $ip = OfficeIpAllowlist::findOrFail($id);
        $newStatus = !$ip->is_active;

        $ip->forceFill(['is_active' => $newStatus])->save();

        $this->auditLogger->log(
            action: 'ip_allowlist.status_toggled',
            targetType: 'App\Models\OfficeIpAllowlist',
            targetId: $ip->id,
            afterValues: ['is_active' => $newStatus],
            description: "Toggled status of office IP {$ip->ip_address} to " . ($newStatus ? 'Active' : 'Inactive') . "."
        );

        $label = $newStatus ? 'enabled' : 'disabled';
        return back()->with('success', "Office IP {$ip->ip_address} has been {$label}.");
    }

    /**
     * Remove the specified IP from allowlist.
     */
    public function destroy($id): RedirectResponse
    {
        $ip = OfficeIpAllowlist::findOrFail($id);
        $ipAddress = $ip->ip_address;
        $ip->delete();

        $this->auditLogger->log(
            action: 'ip_allowlist.deleted',
            targetType: 'App\Models\OfficeIpAllowlist',
            targetId: $id,
            description: "Removed office IP {$ipAddress} from allowlist."
        );

        return back()->with('success', "Office IP {$ipAddress} removed from allowlist.");
    }
}
