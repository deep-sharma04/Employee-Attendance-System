<?php

namespace App\Services\AI\Tools;

use App\Models\User;
use App\Services\AI\ProjectIntelligenceService;

class IntelligenceMcpTools
{
    public function __construct(
        protected ProjectIntelligenceService $intelligenceService
    ) {}

    /**
     * T285: project.intelligence_search
     */
    public function search(User $user, array $args): array
    {
        return $this->intelligenceService->searchProjectIntelligence($user, $args);
    }

    /**
     * T286: project.explain_health
     */
    public function explainHealth(User $user, array $args): array
    {
        if (empty($args['project_id'])) {
            throw new \InvalidArgumentException('Missing required parameter: project_id');
        }

        return $this->intelligenceService->explainProjectHealth($user, (int) $args['project_id']);
    }

    /**
     * T287: task.recommend_allocation
     */
    public function recommendAllocation(User $user, array $args): array
    {
        return $this->intelligenceService->recommendTaskAllocation($user, $args);
    }

    /**
     * T288: project.management_report
     */
    public function managementReport(User $user, array $args): array
    {
        return $this->intelligenceService->generateManagementReport($user, $args);
    }
}
