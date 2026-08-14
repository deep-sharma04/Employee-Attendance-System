<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreHrAdminRequest;
use App\Http\Requests\SuperAdmin\UpdateHrAdminRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HrAdminManagementController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * Display a listing of all HR Admin accounts with status and quick actions.
     */
    public function index(Request $request): View
    {
        $query = User::where(function ($q) {
            $q->where('role', UserRole::HR_ADMIN->value)
              ->orWhere('role', 'hr_admin')
              ->orWhereHas('roles', fn($r) => $r->where('slug', UserRole::HR_ADMIN->value));
        })->latest('id');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $isActive = $request->input('status') === 'active';
            $query->where('is_active', $isActive);
        }

        $hrAdmins = $query->paginate(15)->withQueryString();

        return view('super-admin.hr-admins.index', compact('hrAdmins'));
    }

    /**
     * Show the form for creating a new HR Admin.
     */
    public function create(): View
    {
        return view('super-admin.hr-admins.create');
    }

    public function store(StoreHrAdminRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $rawPassword = $validated['password'] ?? null;
        if (empty($rawPassword) || $request->boolean('auto_generate_password')) {
            $rawPassword = 'Hr@' . Str::random(8) . '!';
        }

        $hrAdmin = DB::transaction(function () use ($validated, $rawPassword, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($rawPassword),
                'role' => UserRole::HR_ADMIN,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
                'email_verified_at' => now(),
            ]);

            $hrRole = Role::firstOrCreate(['slug' => UserRole::HR_ADMIN->value], ['name' => 'HR Admin']);
            $user->roles()->syncWithoutDetaching([$hrRole->id]);

            $this->auditLogger->log(
                action: 'hr_admin.created',
                targetType: 'User',
                targetId: $user->id,
                afterValues: [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => 'hr_admin',
                    'is_active' => $user->is_active,
                ],
                description: "Super Admin created HR Admin account '{$user->name}' ({$user->username})."
            );

            return $user;
        });

        session()->flash('temp_credentials_username', $hrAdmin->username);
        session()->flash('temp_credentials_password', $rawPassword);

        return redirect()->route('super-admin.hr-admins.index')
            ->with('success', "HR Admin '{$hrAdmin->name}' created successfully.");
    }

    /**
     * Show the form for editing an HR Admin account.
     */
    public function edit(int $id): View
    {
        $hrAdmin = User::findOrFail($id);

        return view('super-admin.hr-admins.edit', compact('hrAdmin'));
    }

    /**
     * Update an HR Admin account details.
     */
    public function update(UpdateHrAdminRequest $request, int $id): RedirectResponse
    {
        $hrAdmin = User::findOrFail($id);
        $validated = $request->validated();

        $beforeValues = [
            'name' => $hrAdmin->name,
            'email' => $hrAdmin->email,
            'is_active' => $hrAdmin->is_active,
        ];

        DB::transaction(function () use ($hrAdmin, $validated, $beforeValues, $request) {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $hrAdmin->is_active,
            ];

            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $hrAdmin->update($updateData);

            $this->auditLogger->log(
                action: 'hr_admin.updated',
                targetType: 'User',
                targetId: $hrAdmin->id,
                beforeValues: $beforeValues,
                afterValues: [
                    'name' => $hrAdmin->name,
                    'email' => $hrAdmin->email,
                    'is_active' => $hrAdmin->is_active,
                ],
                description: "Super Admin updated HR Admin account '{$hrAdmin->name}' ({$hrAdmin->username})."
            );
        });

        return redirect()->route('super-admin.hr-admins.index')
            ->with('success', "HR Admin '{$hrAdmin->name}' updated successfully.");
    }

    /**
     * Toggle active/suspended status of an HR Admin account.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $hrAdmin = User::findOrFail($id);
        $newStatus = !$hrAdmin->is_active;

        $hrAdmin->update(['is_active' => $newStatus]);

        $action = $newStatus ? 'hr_admin.activated' : 'hr_admin.suspended';
        $statusText = $newStatus ? 'activated' : 'suspended';

        $this->auditLogger->log(
            action: $action,
            targetType: 'User',
            targetId: $hrAdmin->id,
            beforeValues: ['is_active' => !$newStatus],
            afterValues: ['is_active' => $newStatus],
            description: "Super Admin {$statusText} HR Admin account '{$hrAdmin->name}' ({$hrAdmin->username})."
        );

        return redirect()->route('super-admin.hr-admins.index')
            ->with('success', "HR Admin '{$hrAdmin->name}' has been {$statusText}.");
    }
}
