<?php

namespace Platform\Occupational\Livewire\Employee;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Occupational\Models\Employment as EmploymentModel;

class Show extends Component
{
    #[Locked]
    public int $employmentId;

    public array $form = [];

    protected array $fields = [
        'position', 'personnel_number', 'company_id', 'started_at', 'ended_at',
        'active', 'first_aider', 'work_notes', 'risk',
    ];

    public function mount(int $employment): void
    {
        $model = $this->resolve($employment);
        $this->employmentId = $model->id;

        foreach ($this->fields as $f) {
            $value = $model->{$f};
            if (in_array($f, ['started_at', 'ended_at'], true)) {
                $value = optional($value)->format('Y-m-d');
            }
            $this->form[$f] = $value;
        }
    }

    protected function resolve(int $id): EmploymentModel
    {
        $team = Auth::user()->currentTeam;

        return EmploymentModel::query()->forTeam($team->id)->findOrFail($id);
    }

    protected function rules(): array
    {
        return [
            'form.position'         => ['nullable', 'string', 'max:255'],
            'form.personnel_number' => ['nullable', 'string', 'max:64'],
            'form.company_id'       => ['nullable', 'integer'],
            'form.started_at'       => ['nullable', 'date'],
            'form.ended_at'         => ['nullable', 'date'],
            'form.active'           => ['boolean'],
            'form.first_aider'      => ['boolean'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $model = $this->resolve($this->employmentId);

        $data = [];
        foreach ($this->fields as $f) {
            $data[$f] = $this->form[$f] === '' ? null : $this->form[$f];
        }
        $data['active'] = (bool) ($this->form['active'] ?? false);
        $data['first_aider'] = (bool) ($this->form['first_aider'] ?? false);

        $model->update($data);

        $this->dispatch('toast', message: 'Beschäftigte:r gespeichert.', type: 'success');
    }

    public function delete()
    {
        $this->resolve($this->employmentId)->delete();

        return $this->redirectRoute('occupational.employees.index', navigate: true);
    }

    public function render()
    {
        $model = $this->resolve($this->employmentId)->load('patient');

        return view('occupational::livewire.employee.show', [
            'employment' => $model,
        ])->layout('platform::layouts.app');
    }
}
