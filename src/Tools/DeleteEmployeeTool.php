<?php

namespace Platform\Occupational\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Occupational\Models\Employment;
use Platform\Occupational\Tools\Concerns\ResolvesOccupationalTeam;

class DeleteEmployeeTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesOccupationalTeam;

    public function getName(): string
    {
        return 'occupational.employees.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /occupational/employees - Deletes an employment. REQUIRED: employment_id.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: team id. Default: current team.'],
                'employment_id' => ['type' => 'integer', 'description' => 'Id of the employment (REQUIRED).'],
            ],
            'required' => ['employment_id'],
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

            $id = (int) ($arguments['employment_id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'employment_id is required.');
            }

            $employment = Employment::query()->forTeam($teamId)->find($id);
            if (!$employment) {
                return ToolResult::error('NOT_FOUND', 'Employment not found (or no access).');
            }

            $employment->delete();

            return ToolResult::success(['id' => $id, 'message' => 'Employment deleted.']);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error deleting employment: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false, 'category' => 'action', 'tags' => ['occupational', 'employees', 'delete'],
            'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false,
        ];
    }
}
