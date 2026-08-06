<?php

namespace Platform\Occupational\Patient;

use Platform\Patient\Contracts\PatientPanelProvider;
use Platform\Occupational\Models\Employment;

/**
 * Steuert das „Beschäftigung"-Panel zur Patienten-Akte bei (Betrieb/Abteilung).
 * Liefert NULL ohne Beschäftigung → bei Nicht-Betriebs-Patienten erscheint kein Panel.
 */
class OccupationalPatientPanel implements PatientPanelProvider
{
    public function panel(int $patientId, int $teamId): ?array
    {
        $employments = Employment::query()
            ->forTeam($teamId)
            ->where('patient_id', $patientId)
            ->with('organizationEntity')
            ->orderByDesc('active')
            ->orderByDesc('id')
            ->get();

        if ($employments->isEmpty()) {
            return null;
        }

        $items = $employments->map(function (Employment $e) {
            $betrieb = $e->organizationEntity?->name;

            return [
                'title'    => ($e->position ?: 'Beschäftigung') . ($betrieb ? ' @ ' . $betrieb : ''),
                'subtitle' => $e->active ? 'aktiv' : 'inaktiv',
                'meta'     => $e->started_at ? 'seit ' . $e->started_at->format('d.m.Y') : null,
                'url'      => route('occupational.employees.show', $e->id),
            ];
        })->all();

        return [
            'key'   => 'employment',
            'title' => 'Beschäftigung',
            'icon'  => 'briefcase',
            'order' => 10,
            'items' => $items,
        ];
    }
}
