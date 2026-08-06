<?php

namespace Platform\Occupational\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Occupational\Models\Employment;
use Platform\Occupational\Tools\Concerns\ResolvesOccupationalTeam;

class GetEmployeeTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOccupationalTeam;

    public function getName(): string
    {
        return 'occupational.employee.GET';
    }

    public function getDescription(): string
    {
        return 'GET /occupational/employee - Shows a single employment. REQUIRED: employment_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: team id. Default: current team.'],
                'employment_id' => ['type' => 'integer', 'description' => 'Id of the employment (REQUIRED).'],
            ],
            'required' => ['employment_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int) $resolved['team_id'];

            $id = (int) ($arguments['employment_id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'employment_id is required.');
            }

            $e = Employment::query()->forTeam($teamId)->with('patient')->find($id);
            if (!$e) {
                return ToolResult::error('NOT_FOUND', 'Employment not found (or no access).');
            }

            return ToolResult::success([
                'id' => $e->id,
                'patient_id' => $e->patient_id,
                'patient_name' => $e->patient?->getDisplayName(),
                'company_id' => $e->company_id,
                'position' => $e->position,
                'personnel_number' => $e->personnel_number,
                'started_at' => optional($e->started_at)->toDateString(),
                'ended_at' => optional($e->ended_at)->toDateString(),
                'active' => (bool) $e->active,
                'first_aider' => (bool) $e->first_aider,
                'work_notes' => $e->work_notes,
                'risk' => $e->risk,
                'team_id' => $e->team_id,
                'created_at' => $e->created_at?->toISOString(),
                'updated_at' => $e->updated_at?->toISOString(),
            ]);
        } catch (\Throwable $ex) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading employment: ' . $ex->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true, 'category' => 'read', 'tags' => ['occupational', 'employee', 'get'],
            'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
