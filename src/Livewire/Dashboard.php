<?php

namespace Platform\Occupational\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Occupational\Models\Employment as EmploymentModel;

class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        $team = $user?->currentTeam;

        $stats = ['companies' => 0, 'employees' => 0];
        $betriebe = [];

        if ($team) {
            $stats['employees'] = EmploymentModel::query()->forTeam($team->id)->count();
            $stats['companies'] = EmploymentModel::query()
                ->forTeam($team->id)
                ->whereNotNull('organization_entity_id')
                ->distinct()
                ->count('organization_entity_id');
            $betriebe = $this->betriebe($team->id);
        }

        return view('occupational::livewire.dashboard', [
            'stats'           => $stats,
            'betriebe'        => $betriebe,
            // Zweiter Eingang zur Vorsorgekartei: sie lebt im customer-Betrieb — nur verlinken, wenn da.
            'karteiAvailable' => \Illuminate\Support\Facades\Route::has('customer.companies.vorsorge'),
            'currentDate'     => now()->format('d.m.Y'),
        ])->layout('platform::layouts.app');
    }

    /** Betriebe (Org-Entities) mit aktiven Beschäftigten + Name + Anzahl. */
    protected function betriebe(int $teamId): array
    {
        $out = [];
        foreach (EmploymentModel::query()->forTeam($teamId)->where('active', true)
                    ->with('organizationEntity')->get() as $e) {
            $id = (int) $e->organization_entity_id;
            if (!$id) {
                continue;
            }
            $out[$id] ??= ['id' => $id, 'name' => $e->organizationEntity?->name ?: ('Betrieb #' . $id), 'count' => 0];
            $out[$id]['count']++;
        }
        usort($out, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $out;
    }
}
