<?php

namespace Platform\Occupational\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Occupational\Models\Employment;
use Platform\Occupational\Tools\Concerns\ResolvesOccupationalTeam;

class ListEmployeesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesOccupationalTeam;

    public function getName(): string
    {
        return 'occupational.employees.GET';
    }

    public function getDescription(): string
    {
        return 'GET /occupational/employees - Lists employments (patient ↔ company). Params: team_id (optional), company_id (optional), active (optional), sort/limit/offset.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas($this->getStandardGetSchema(), [
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: team id. Default: current team.'],
                'company_id' => ['type' => 'integer', 'description' => 'Optional: filter by CRM company id.'],
                'active' => ['type' => 'boolean', 'description' => 'Optional: filter by active flag.'],
            ],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int) $resolved['team_id'];

            $query = Employment::query()->forTeam($teamId)->with('patient');

            if (isset($arguments['company_id'])) {
                $query->where('company_id', (int) $arguments['company_id']);
            }
            if (array_key_exists('active', $arguments) && $arguments['active'] !== null) {
                $query->where('active', (bool) $arguments['active']);
            }

            $this->applyStandardFilters($query, $arguments, ['company_id', 'active', 'created_at']);
            $this->applyStandardSort($query, $arguments, ['created_at', 'started_at', 'position'], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(fn (Employment $e) => [
                'id' => $e->id,
                'patient_id' => $e->patient_id,
                'patient_name' => $e->patient?->getDisplayName(),
                'company_id' => $e->company_id,
                'position' => $e->position,
                'active' => (bool) $e->active,
                'team_id' => $e->team_id,
            ])->values()->toArray();

            return ToolResult::success(['data' => $data, 'pagination' => $result['pagination'] ?? null, 'team_id' => $teamId]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading employees: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true, 'category' => 'read', 'tags' => ['occupational', 'employees', 'list'],
            'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
