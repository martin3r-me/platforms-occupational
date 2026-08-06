<?php

namespace Platform\Occupational\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

/**
 * Occupational-Haupt-Sidebar — gleiche Betrieb-Baum-Navigation wie customer/patient/encounter.
 * Klick auf einen Knoten zeigt dessen Beschäftigte (occupational.employees.index?node=). Der
 * Baum kommt aus customer (guarded).
 */
class Sidebar extends Component
{
    public function render()
    {
        $team = Auth::user()?->currentTeam?->id;

        $nodes = [];
        if ($team && class_exists(\Platform\Customer\Support\Companies::class)) {
            foreach (\Platform\Customer\Support\Companies::tree((int) $team) as $n) {
                $nodes[] = [
                    'id'    => $n['id'],
                    'label' => $n['name'],
                    'depth' => $n['depth'],
                    'url'   => route('occupational.employees.index', ['node' => $n['id']]),
                ];
            }
        }

        return view('occupational::livewire.sidebar', [
            'nodes'    => $nodes,
            'activeId' => request()->query('node'),
        ]);
    }
}
