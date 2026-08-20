<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\Client;
use App\Models\Document;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\Team;
use App\Policies\AttendancePolicy;
use App\Policies\ClientPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\LeavePolicy;
use App\Policies\PayrollPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\TeamPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Policy mappings.
     */
    protected array $policies = [
        Employee::class => EmployeePolicy::class,
        AttendanceRecord::class => AttendancePolicy::class,
        LeaveRequest::class => LeavePolicy::class,
        Document::class => DocumentPolicy::class,
        Payroll::class => PayrollPolicy::class,
        Client::class => ClientPolicy::class,
        Team::class => TeamPolicy::class,
        Project::class => ProjectPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\AI\McpIntegrationService::class);
        $this->app->singleton(\App\Services\AI\McpToolRegistry::class);
        $this->app->singleton(\App\Services\AI\McpSecurityGuard::class);
        $this->app->singleton(\App\Services\AI\McpUsagePolicyService::class);
        $this->app->singleton(\App\Services\AI\McpAuthService::class);
        $this->app->singleton(\App\Services\AI\ProjectIntelligenceService::class);
        $this->app->singleton(\App\Services\AI\Tools\IntelligenceMcpTools::class);
        $this->app->singleton(\App\Services\AI\Workflow\McpWorkflowExecutionService::class);
        $this->app->singleton(\App\Services\AI\Tools\WorkflowMcpTools::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enforce strict Eloquent model attributes protection (T169)
        Model::preventSilentlyDiscardingAttributes(!app()->isProduction());

        if (class_exists(\Laravel\Passport\Passport::class)) {
            \Laravel\Passport\Passport::tokensCan([
                'mcp' => 'Access MCP Server',
            ]);
        }

        // Enforce HTTPS in production environments or when configured (T175)
        if (app()->environment('production') || config('app.force_https', false)) {
            URL::forceScheme('https');
        }

        // Apply dynamic SMTP & mail settings from company settings in non-testing environments
        if (!app()->environment('testing')) {
            try {
                $this->app->make(\App\Services\Settings\SettingsService::class)->applyMailConfiguration();
            } catch (\Throwable $e) {
                // Ignore during early migrations or when database table is not yet created
            }
        }

        // Register model policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Global RBAC Gates
        Gate::define('manage-super-admin', function ($user) {
            $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
            return $role === UserRole::SUPER_ADMIN->value;
        });

        Gate::define('manage-hr-admin', function ($user) {
            $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
            return in_array($role, [UserRole::SUPER_ADMIN->value, UserRole::HR_ADMIN->value]);
        });

        Gate::define('manage-projects', function ($user) {
            $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
            return in_array($role, [UserRole::SUPER_ADMIN->value, UserRole::MANAGER->value]);
        });

        Gate::define('access-client-portal', function ($user) {
            $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
            return $role === UserRole::CLIENT->value;
        });

        // Enforce own-data access guard (T035)
        Gate::define('access-own-employee', function ($user, $employeeId) {
            $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

            if (in_array($role, [UserRole::SUPER_ADMIN->value, UserRole::HR_ADMIN->value])) {
                return true;
            }

            if ($role === UserRole::EMPLOYEE->value && $user->employee) {
                return (int) $user->employee->id === (int) $employeeId;
            }

            return false;
        });

        // Phase 31: Register Internal MCP Server & Tools (T276, T278)
        if (class_exists(\Laravel\Mcp\Facades\Mcp::class)) {
            \Laravel\Mcp\Facades\Mcp::local('hrm', \App\Services\AI\HrmMcpServer::class);
            \Laravel\Mcp\Facades\Mcp::web('/mcp', \App\Services\AI\HrmMcpServer::class)
                ->middleware([\App\Http\Middleware\AuthenticateRemoteMcp::class]);

            \Laravel\Mcp\Facades\Mcp::oauthRoutes();
            
            // Initialize tool registry singleton mapping to McpIntegrationService
            $this->app->make(\App\Services\AI\McpToolRegistry::class);
        }
    }
}
