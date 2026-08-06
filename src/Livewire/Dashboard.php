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

        if ($team) {
            $stats['employees'] = EmploymentModel::query()->forTeam($team->id)->count();
            $stats['companies'] = EmploymentModel::query()
                ->forTeam($team->id)
                ->whereNotNull('organization_entity_id')
                ->distinct()
                ->count('organization_entity_id');
        }

        return view('occupational::livewire.dashboard', [
            'stats'       => $stats,
            'currentDate' => now()->format('d.m.Y'),
        ])->layout('platform::layouts.app');
    }
}
