<?php

namespace Platform\Occupational\Company;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Platform\Customer\Contracts\CompanyProvisionProvider;
use Platform\Occupational\Models\Employment;
use Platform\Occupational\Models\Provision;

/**
 * Liefert dem Betrieb-Cockpit (customer) die Vorsorgekartei bei: je Beschäftigtem des
 * Betrieb-Teilbaums die Vorsorgen mit Anlass/Art/Frist + Status (überfällig/fällig/offen).
 * Nur Navigations-/Statusliste (Name + URL) — keine Befunde (Schweigepflicht).
 */
class OccupationalCompanyProvisionProvider implements CompanyProvisionProvider
{
    /** Fenster für „fällig demnächst" (Tage). */
    private const LOOKAHEAD_DAYS = 60;

    public function provisionsFor(array $entityIds, int $teamId): array
    {
        if (empty($entityIds)) {
            return [];
        }

        // Aktive Beschäftigungen des Teilbaums → patient_id ⇒ Beschäftigung (Name/Position).
        $empByPatient = [];
        foreach (Employment::query()->forTeam($teamId)
                    ->whereIn('organization_entity_id', $entityIds)
                    ->where('active', true)
                    ->with('patient')->get() as $e) {
            if ($e->patient) {
                $empByPatient[(int) $e->patient->id] = $e;
            }
        }
        if (empty($empByPatient)) {
            return [];
        }

        $today = Carbon::now()->startOfDay();
        $soon  = $today->copy()->addDays(self::LOOKAHEAD_DAYS);

        $rows = [];
        foreach (Provision::query()->forTeam($teamId)
                    ->whereIn('patient_id', array_keys($empByPatient))
                    ->with('occasion')->get() as $p) {
            $e = $empByPatient[(int) $p->patient_id] ?? null;
            if (!$e || !$e->patient) {
                continue;
            }
            $patient = $e->patient;
            $due     = $p->next_due_at;

            if (!$due) {
                $status = 'open';
            } elseif ($due->lt($today)) {
                $status = 'overdue';
            } elseif ($due->lte($soon)) {
                $status = 'due';
            } else {
                $status = 'ok';
            }

            $url = Route::has('encounter.akte.show')
                ? route('encounter.akte.show', $patient->id)
                : route('patient.patients.show', $patient->id);

            $rows[] = [
                'patient_id'      => (int) $patient->id,
                'patient_name'    => $patient->getDisplayName() ?? ('Patient #' . $patient->id),
                'position'        => $e->position ?: null,
                'occasion'        => optional($p->occasion)->title,
                'care_type'       => $p->type?->value,
                'care_type_label' => $p->type?->label(),
                'next_due'        => optional($due)->toDateString(),
                'status'          => $status,
                'url'             => $url,
            ];
        }

        return $rows;
    }
}
