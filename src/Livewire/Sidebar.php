<?php

namespace Platform\Occupational\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Occupational\Models\Employment as EmploymentModel;

class Sidebar extends Component
{
    public function render()
    {
        $user = Auth::user();
        $employments = collect();

        if ($user && $user->currentTeam) {
            $employments = EmploymentModel::query()
                ->forTeam($user->currentTeam->id)
                ->with('patient')
                ->orderByDesc('id')
                ->limit(15)
                ->get();
        }

        return view('occupational::livewire.sidebar', [
            'employments' => $employments,
        ]);
    }
}
