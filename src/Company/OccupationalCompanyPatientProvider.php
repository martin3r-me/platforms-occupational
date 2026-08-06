<?php

namespace Platform\Occupational\Company;

use Platform\Customer\Contracts\CompanyPatientProvider;
use Platform\Occupational\Models\Employment;

/**
 * Liefert dem Betrieb-Cockpit (customer) die Patienten bei, die über eine Beschäftigung
 * an diesem Betrieb/Teilbaum hängen. Nur Navigations-Liste (Name + URL zur Patienten-Akte)
 * — keine Patientendaten verlassen das patient-Modul.
 */
class OccupationalCompanyPatientProvider implements CompanyPatientProvider
{
    public function patientsFor(array $entityIds, int $teamId): array
    {
        $employments = Employment::query()
            ->forTeam($teamId)
            ->whereIn('organization_entity_id', $entityIds)
            ->with(['patient', 'organizationEntity'])
            ->orderByDesc('active')
            ->orderByDesc('id')
            ->get();

        $rows = [];
        foreach ($employments as $e) {
            $patient = $e->patient;
            if (!$patient) {
                continue;
            }

            // Person-Klick landet primär in der Akte (Verlauf), Stammdaten von dort ein Klick weg.
            $url = \Illuminate\Support\Facades\Route::has('encounter.akte.show')
                ? route('encounter.akte.show', $patient->id)
                : route('patient.patients.show', $patient->id);

            $rows[] = [
                'patient_id' => (int) $patient->id,
                'name'       => $patient->getDisplayName() ?? ('Patient #' . $patient->id),
                'subtitle'   => $e->position ?: null,
                'meta'       => $e->organizationEntity?->name,
                'url'        => $url,
            ];
        }

        return $rows;
    }
}
