<?php

namespace Platform\Occupational\Livewire\Employee;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Occupational\Models\Employment as EmploymentModel;
use Platform\Occupational\Models\Provision;
use Platform\Occupational\Enums\CareType;
use Platform\Occupational\Support\Betriebe;

class Show extends Component
{
    #[Locked]
    public int $employmentId;

    public array $form = [];

    protected array $fields = [
        'position', 'personnel_number', 'organization_entity_id', 'started_at', 'ended_at',
        'active', 'first_aider', 'work_notes', 'risk',
    ];

    public bool $showProvisionModal = false;
    public array $provisionForm = [
        'occasion_id'     => null,
        'type'            => 'mandatory',
        'interval_months' => null,
        'next_due_at'     => null,
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
            'form.position'                => ['nullable', 'string', 'max:255'],
            'form.personnel_number'        => ['nullable', 'string', 'max:64'],
            'form.organization_entity_id'  => ['nullable', 'integer'],
            'form.started_at'              => ['nullable', 'date'],
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

    public function createProvision(): void
    {
        $employment = $this->resolve($this->employmentId);
        if (!$employment->patient_id) {
            return;
        }

        $occasionId = $this->provisionForm['occasion_id'] ?: null;

        Provision::create([
            'patient_id'      => $employment->patient_id,
            'occasion_type'   => $occasionId ? 'arbmedvv_occasion' : null,
            'occasion_id'     => $occasionId,
            'type'            => $this->provisionForm['type'] ?: 'mandatory',
            'interval_months' => $this->provisionForm['interval_months'] ?: null,
            'next_due_at'     => $this->provisionForm['next_due_at'] ?: null,
            'created_by_user_id' => Auth::id(),
        ]);

        $this->provisionForm = ['occasion_id' => null, 'type' => 'mandatory', 'interval_months' => null, 'next_due_at' => null];
        $this->showProvisionModal = false;
    }

    /**
     * GBU→Vorsorge-Brücke: leitet aus den Gefährdungsbeurteilungen des Betriebs
     * (customer) die empfohlenen Vorsorgen ab und legt fehlende Provisions an.
     */
    public function deriveProvisionsFromGbu(): void
    {
        $team = (int) Auth::user()->currentTeam->id;
        $employment = $this->resolve($this->employmentId);

        if (!$employment->patient_id || !$employment->organization_entity_id
            || !class_exists(\Platform\Customer\Models\Hazard::class)
            || !class_exists(\Platform\Customer\Support\Companies::class)) {
            $this->dispatch('toast', message: 'Keine Gefährdungsbeurteilung verfügbar.', type: 'info');
            return;
        }

        $ids = \Platform\Customer\Support\Companies::subtreeIds((int) $employment->organization_entity_id, $team);

        $hazards = \Platform\Customer\Models\Hazard::query()
            ->where('team_id', $team)
            ->where('catalog_type', 'arbmedvv_occasion')
            ->whereNotNull('catalog_id')
            ->whereHas('riskAssessment', fn ($q) => $q->whereIn('organization_entity_id', $ids))
            ->get();

        $existing = Provision::query()->forTeam($team)
            ->where('patient_id', $employment->patient_id)
            ->where('occasion_type', 'arbmedvv_occasion')
            ->pluck('occasion_id')->filter()->all();

        $created = 0;
        $seen = [];
        foreach ($hazards as $h) {
            $occasionId = (int) $h->catalog_id;
            if (in_array($occasionId, $existing, true) || in_array($occasionId, $seen, true)) {
                continue;
            }
            $seen[] = $occasionId;

            Provision::create([
                'patient_id'         => $employment->patient_id,
                'occasion_type'      => 'arbmedvv_occasion',
                'occasion_id'        => $occasionId,
                'type'               => $h->care_type ?: 'mandatory',
                'created_by_user_id' => Auth::id(),
            ]);
            $created++;
        }

        $this->dispatch('toast',
            message: $created > 0 ? "{$created} Vorsorge(n) aus der Gefährdungsbeurteilung abgeleitet." : 'Keine neuen Vorsorgen — alles bereits vorhanden.',
            type: 'success');
    }

    public function render()
    {
        $team  = (int) Auth::user()->currentTeam->id;
        $model = $this->resolve($this->employmentId)->load(['patient', 'organizationEntity']);

        $provisions = Provision::query()
            ->forTeam($team)
            ->where('patient_id', $model->patient_id)
            ->with('occasion')
            ->orderByRaw('next_due_at is null, next_due_at asc')
            ->get();

        $occasionOptions = [];
        if (class_exists(\Platform\Arbmedvv\Models\Occasion::class)) {
            $occasionOptions = \Platform\Arbmedvv\Models\Occasion::query()
                ->where('team_id', $team)
                ->orderBy('title')
                ->get()
                ->map(fn ($o) => ['value' => $o->id, 'label' => $o->title])
                ->all();
        }

        $careTypeOptions = collect(CareType::cases())
            ->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])
            ->all();

        // Link zur Patienten-Akte (Stammdaten leben dort, nicht hier).
        $akteUrl = $model->patient_id ? route('patient.patients.show', $model->patient_id) : null;

        // Kollegen im selben Betrieb-Teilbaum (innere Sidebar).
        $colleagues = collect();
        if ($model->organization_entity_id && class_exists(\Platform\Customer\Support\Companies::class)) {
            $ids = \Platform\Customer\Support\Companies::subtreeIds((int) $model->organization_entity_id, $team);
            $colleagues = EmploymentModel::query()
                ->forTeam($team)
                ->whereIn('organization_entity_id', $ids)
                ->with('patient')
                ->orderByDesc('active')
                ->get();
        }

        return view('occupational::livewire.employee.show', [
            'employment'      => $model,
            'betriebOptions'  => Betriebe::options($team),
            'provisions'      => $provisions,
            'occasionOptions' => $occasionOptions,
            'careTypeOptions' => $careTypeOptions,
            'akteUrl'         => $akteUrl,
            'colleagues'      => $colleagues,
        ])->layout('platform::layouts.app');
    }
}
