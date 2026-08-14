<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'actor_id' => 1,
            'actor_name' => 'Super Administrator',
            'actor_role' => 'super_admin',
            'action' => 'employee.created',
            'target_type' => 'App\Models\Employee',
            'target_id' => 1,
            'before_values' => null,
            'after_values' => ['name' => 'Test Employee'],
            'description' => 'Created test employee record',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 PHPUnit',
            'created_at' => now(),
        ];
    }
}
