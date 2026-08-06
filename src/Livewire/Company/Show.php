<?php

namespace Platform\Occupational\Livewire\Company;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Organization\Models\OrganizationEntity;
use Platform\Occupational\Models\Employment;

/**
 * Betrieb/Abteilung-Detail — Abteilungen (Kinder) + hier verortete Beschäftigte.
 */
class Show extends Component
{
    #[Locked]
    public int $entityId;

    public function mount(int $company): void
    {
        $this->entityId = $this->resolve($company)->id;
    }

    protected function resolve(int $id): OrganizationEntity
    {
        $team = (int) Auth::user()->currentTeam->id;

        return OrganizationEntity::query()->forTeam($team)->findOrFail($id);
    }

    public function render()
    {
        $team = (int) Auth::user()->currentTeam->id;

        $entity = $this->resolve($this->entityId)->load('entityType');

        $parent = $entity->parent_entity_id
            ? OrganizationEntity::query()->whereKey($entity->parent_entity_id)->first(['id', 'name'])
            : null;

        $children = OrganizationEntity::query()
            ->forTeam($team)
            ->where('parent_entity_id', $entity->id)
            ->with('entityType')
            ->orderBy('name')
            ->get();

        $childCounts = Employment::query()
            ->forTeam($team)
            ->whereIn('organization_entity_id', $children->pluck('id'))
            ->selectRaw('organization_entity_id, count(*) as c')
            ->groupBy('organization_entity_id')
            ->pluck('c', 'organization_entity_id');

        $employees = Employment::query()
            ->forTeam($team)
            ->where('organization_entity_id', $entity->id)
            ->with('patient')
            ->orderByDesc('active')
            ->get();

        return view('occupational::livewire.company.show', [
            'entity'      => $entity,
            'parent'      => $parent,
            'children'    => $children,
            'childCounts' => $childCounts,
            'employees'   => $employees,
        ])->layout('platform::layouts.app');
    }
}
