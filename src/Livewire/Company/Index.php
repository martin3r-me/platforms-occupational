<?php

namespace Platform\Occupational\Livewire\Company;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Organization\Models\OrganizationEntity;
use Platform\Organization\Models\OrganizationEntityType;
use Platform\Occupational\Models\Employment;

/**
 * Betriebe-Sicht — spiegelt den Umwelt-Baum (Kunden + Abteilungen) mit Beschäftigten-Zahlen.
 */
class Index extends Component
{
    public function render()
    {
        $team = (int) Auth::user()->currentTeam->id;

        $customerTypeId = OrganizationEntityType::query()->where('code', 'external_customer')->value('id');

        $entities = OrganizationEntity::query()
            ->forTeam($team)
            ->with('entityType')
            ->get(['id', 'name', 'parent_entity_id', 'entity_type_id']);

        $byParent = $entities->groupBy('parent_entity_id');

        $counts = Employment::query()
            ->forTeam($team)
            ->whereNotNull('organization_entity_id')
            ->selectRaw('organization_entity_id, count(*) as c')
            ->groupBy('organization_entity_id')
            ->pluck('c', 'organization_entity_id');

        $roots = $entities->where('entity_type_id', (int) $customerTypeId)->sortBy('name')->values();

        $rows = [];
        $walk = function ($node, int $depth) use (&$walk, $byParent, $counts, &$rows): int {
            $direct = (int) ($counts[$node->id] ?? 0);
            $idx = count($rows);
            $rows[] = [
                'id' => $node->id, 'name' => $node->name,
                'type' => $node->entityType?->name, 'depth' => $depth, 'total' => 0,
            ];
            $total = $direct;
            foreach (($byParent[$node->id] ?? collect())->sortBy('name') as $child) {
                $total += $walk($child, $depth + 1);
            }
            $rows[$idx]['total'] = $total;
            return $total;
        };

        foreach ($roots as $root) {
            $walk($root, 0);
        }

        return view('occupational::livewire.company.index', [
            'rows' => $rows,
        ])->layout('platform::layouts.app');
    }
}
