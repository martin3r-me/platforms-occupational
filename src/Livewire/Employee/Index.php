<?php

namespace Platform\Occupational\Livewire\Employee;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Occupational\Models\Employment as EmploymentModel;
use Platform\Patient\Models\Patient as PatientModel;

class Index extends Component
{
    public bool $showCreate = false;
    public ?int $patient_id = null;
    public string $position = '';
    public ?int $company_id = null;

    protected function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer'],
            'position'   => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer'],
        ];
    }

    public function updatedShowCreate(): void
    {
        $this->reset(['patient_id', 'position', 'company_id']);
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
            'patient_id' => $patient->id,
            'position'   => $this->position ?: null,
            'company_id' => $this->company_id ?: null,
            'active'     => true,
        ]);

        return $this->redirectRoute('occupational.employees.show', ['employment' => $employment->id], navigate: true);
    }

    public function render()
    {
        $team = Auth::user()->currentTeam;

        $employments = EmploymentModel::query()
            ->forTeam($team->id)
            ->with('patient')
            ->orderByDesc('id')
            ->get();

        $patients = PatientModel::query()
            ->forTeam($team->id)
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        return view('occupational::livewire.employee.index', [
            'employments' => $employments,
            'patients'    => $patients,
        ])->layout('platform::layouts.app');
    }
}
