<?php

namespace Platform\Occupational\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Occupational\Models\Employment;
use Platform\Occupational\Tools\Concerns\ResolvesOccupationalTeam;

class UpdateEmployeeTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesOccupationalTeam;

    public function getName(): string
    {
        return 'occupational.employees.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /occupational/employees - Updates an employment. REQUIRED: employment_id. Optional: organization_entity_id, position, personnel_number, started_at, ended_at, active, first_aider, work_notes, risk (empty string clears text fields).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: team id. Default: current team.'],
                'employment_id' => ['type' => 'integer', 'description' => 'Id of the employment (REQUIRED).'],
                'organization_entity_id' => ['type' => 'integer', 'description' => 'Optional: Betrieb-Organization-Entity-ID.'],
                'position' => ['type' => 'string', 'description' => 'Optional.'],
                'personnel_number' => ['type' => 'string', 'description' => 'Optional.'],
                'started_at' => ['type' => 'string', 'description' => 'Optional: date YYYY-MM-DD.'],
                'ended_at' => ['type' => 'string', 'description' => 'Optional: date YYYY-MM-DD.'],
                'active' => ['type' => 'boolean', 'description' => 'Optional.'],
                'first_aider' => ['type' => 'boolean', 'description' => 'Optional.'],
                'work_notes' => ['type' => 'string', 'description' => 'Optional.'],
                'risk' => ['type' => 'string', 'description' => 'Optional.'],
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

            $payload = [];
            if (isset($arguments['organization_entity_id'])) {
                $payload['organization_entity_id'] = $arguments['organization_entity_id'] === '' ? null : (int) $arguments['organization_entity_id'];
            }
            if (array_key_exists('active', $arguments) && $arguments['active'] !== null) {
                $payload['active'] = (bool) $arguments['active'];
            }
            if (array_key_exists('first_aider', $arguments) && $arguments['first_aider'] !== null) {
                $payload['first_aider'] = (bool) $arguments['first_aider'];
            }
            foreach (['position', 'personnel_number', 'started_at', 'ended_at', 'work_notes', 'risk'] as $f) {
                if (array_key_exists($f, $arguments)) {
                    $payload[$f] = ($arguments[$f] === '' || $arguments[$f] === null) ? null : (string) $arguments[$f];
                }
            }

            if (empty($payload)) {
                return ToolResult::error('NO_CHANGE', 'No changes provided.');
            }

            $employment->update($payload);

            return ToolResult::success([
                'id' => $employment->id,
                'team_id' => $employment->team_id,
                'message' => 'Employment updated successfully.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error updating employment: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false, 'category' => 'action', 'tags' => ['occupational', 'employees', 'update'],
            'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
