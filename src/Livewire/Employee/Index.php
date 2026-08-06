<?php

namespace Platform\Occupational\Livewire\Employee;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Occupational\Models\Employment as EmploymentModel;
use Platform\Occupational\Support\Betriebe;
use Platform\Patient\Models\Patient as PatientModel;

class Index extends Component
{
    public bool $showCreate = false;
    public ?int $patient_id = null;
    public string $position = '';
    public ?int $organization_entity_id = null;

    protected function rules(): array
    {
        return [
            'patient_id'             => ['required', 'integer'],
            'position'               => ['nullable', 'string', 'max:255'],
            'organization_entity_id' => ['nullable', 'integer'],
        ];
    }

    public function updatedShowCreate(): void
    {
        $this->reset(['patient_id', 'position', 'organization_entity_id']);
        $this->resetValidation();
    }

    public function create()
    {
        $this->validate();

        $team = Auth::user()->currentTeam;

        $patient = PatientModel::query()->forTeam($team->id)->find($this->patient_id);
        if (!$patient) {
            $this->addError('patient_id', 'Patient nicht gefunden.');
            return;
        }

        $employment = EmploymentModel::create([
            'patient_id'             => $patient->id,
            'position'               => $this->position ?: null,
            'organization_entity_id' => $this->organization_entity_id ?: null,
            'active'                 => true,
        ]);

        return $this->redirectRoute('occupational.employees.show', ['employment' => $employment->id], navigate: true);
    }

    public function render()
    {
        $team = Auth::user()->currentTeam;

        $query = EmploymentModel::query()
            ->forTeam($team->id)
            ->with(['patient', 'organizationEntity'])
            ->orderByDesc('id');

        // Betrieb-Kontext (?node): auf den Teilbaum filtern (guarded via customer).
        $node = request()->query('node');
        $contextLabel = null;
        if ($node && class_exists(\Platform\Customer\Support\Companies::class)) {
            $entityIds = \Platform\Customer\Support\Companies::subtreeIds((int) $node, (int) $team->id);
            $query->whereIn('organization_entity_id', $entityIds);
            $entity = \Platform\Organization\Models\OrganizationEntity::query()->whereKey($node)->first(['name']);
            $contextLabel = $entity?->name;
        }

        $employments = $query->get();

        $patients = PatientModel::query()
            ->forTeam($team->id)
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        return view('occupational::livewire.employee.index', [
            'employments'    => $employments,
            'contextLabel'   => $contextLabel,
            'patientOptions' => $patients->map(fn ($p) => ['value' => $p->id, 'label' => $p->getDisplayName()])->values()->all(),
            'betriebOptions' => Betriebe::options((int) $team->id),
        ])->layout('platform::layouts.app');
    }
}
