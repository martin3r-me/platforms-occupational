<?php

namespace Platform\Occupational\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Occupational\Models\Employment;
use Platform\Occupational\Tools\Concerns\ResolvesOccupationalTeam;
use Platform\Patient\Models\Patient;

class CreateEmployeeTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesOccupationalTeam;

    public function getName(): string
    {
        return 'occupational.employees.POST';
    }

    public function getDescription(): string
    {
        return 'POST /occupational/employees - Creates an employment (patient ↔ company). REQUIRED: patient_id (must belong to the team). Optional: company_id (CRM), position, personnel_number, started_at, ended_at, active (default true), first_aider, work_notes, risk.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: team id. Default: current team.'],
                'patient_id' => ['type' => 'integer', 'description' => 'Patient id (REQUIRED).'],
                'company_id' => ['type' => 'integer', 'description' => 'Optional: CRM company id (employer).'],
                'position' => ['type' => 'string', 'description' => 'Optional.'],
                'personnel_number' => ['type' => 'string', 'description' => 'Optional.'],
                'started_at' => ['type' => 'string', 'description' => 'Optional: date YYYY-MM-DD.'],
                'ended_at' => ['type' => 'string', 'description' => 'Optional: date YYYY-MM-DD.'],
                'active' => ['type' => 'boolean', 'description' => 'Optional: default true.'],
                'first_aider' => ['type' => 'boolean', 'description' => 'Optional.'],
                'work_notes' => ['type' => 'string', 'description' => 'Optional.'],
                'risk' => ['type' => 'string', 'description' => 'Optional.'],
            ],
            'required' => ['patient_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user in context.');
            }

            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int) $resolved['team_id'];

            $patient = Patient::query()->forTeam($teamId)->find((int) ($arguments['patient_id'] ?? 0));
            if (!$patient) {
                return ToolResult::error('VALIDATION_ERROR', 'patient_id not found in this team.');
            }

            $payload = [
                'team_id' => $teamId,
                'patient_id' => $patient->id,
                'active' => array_key_exists('active', $arguments) ? (bool) $arguments['active'] : true,
            ];
            if (isset($arguments['company_id']) && $arguments['company_id'] !== '') {
                $payload['company_id'] = (int) $arguments['company_id'];
            }
            if (array_key_exists('first_aider', $arguments)) {
                $payload['first_aider'] = (bool) $arguments['first_aider'];
            }
            foreach (['position', 'personnel_number', 'started_at', 'ended_at', 'work_notes', 'risk'] as $f) {
                if (array_key_exists($f, $arguments)) {
                    $payload[$f] = ($arguments[$f] === '' || $arguments[$f] === null) ? null : (string) $arguments[$f];
                }
            }

            $employment = Employment::create($payload);

            return ToolResult::success([
                'id' => $employment->id,
                'patient_id' => $employment->patient_id,
                'company_id' => $employment->company_id,
                'team_id' => $employment->team_id,
                'message' => 'Employment created successfully.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error creating employment: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false, 'category' => 'action', 'tags' => ['occupational', 'employees', 'create'],
            'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false,
        ];
    }
}
