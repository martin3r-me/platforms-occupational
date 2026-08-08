<?php

namespace Platform\Occupational\Company;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Platform\Customer\Contracts\CompanyProvisionProvider;
use Platform\Occupational\Enums\CareType;
use Platform\Occupational\Models\ActivityAssignment;
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

        // Ist: vorhandene Provisions je Person. Merkt sich (patient → Anlässe), um Soll-Lücken zu finden.
        $rows = [];
        $provisionOccByPatient = [];
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

            if ($p->occasion_id) {
                $provisionOccByPatient[(int) $p->patient_id][(int) $p->occasion_id] = true;
            }

            $rows[] = [
                'patient_id'      => (int) $patient->id,
                'patient_name'    => $patient->getDisplayName() ?? ('Patient #' . $patient->id),
                'position'        => $e->position ?: null,
                'occasion'        => optional($p->occasion)->title,
                'care_type'       => $p->type?->value,
                'care_type_label' => $p->type?->label(),
                'next_due'        => optional($due)->toDateString(),
                'status'          => $status,
                'url'             => $this->akteUrl((int) $patient->id),
            ];
        }

        // Soll: aus den Tätigkeiten der Personen — Anlässe ohne passende Provision = „fehlt".
        foreach ($this->missingSoll($empByPatient, $provisionOccByPatient, $teamId) as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Soll-Lücken: je Beschäftigung die aktuellen Tätigkeiten → deren aktuelle Anforderungen;
     * jeder geforderte Anlass ohne passende Provision wird als „fehlt"-Zeile ergänzt. Guarded.
     *
     * @param  array<int,\Platform\Occupational\Models\Employment> $empByPatient
     * @param  array<int,array<int,bool>> $provisionOccByPatient
     * @return array<int,array>
     */
    protected function missingSoll(array $empByPatient, array $provisionOccByPatient, int $teamId): array
    {
        if (!class_exists(\Platform\Customer\Models\ActivityRequirement::class)) {
            return [];
        }

        // employment_id → patient_id (für Rückzuordnung der Zuweisungen).
        $patientByEmployment = [];
        foreach ($empByPatient as $pid => $e) {
            $patientByEmployment[(int) $e->id] = (int) $pid;
        }
        if (empty($patientByEmployment)) {
            return [];
        }

        // Aktuelle Tätigkeit-Zuweisungen → patient_id ⇒ [activity_id].
        $activitiesByPatient = [];
        $allActivityIds = [];
        foreach (ActivityAssignment::query()->forTeam($teamId)->current()
                    ->whereIn('employment_id', array_keys($patientByEmployment))->get() as $a) {
            $pid = $patientByEmployment[(int) $a->employment_id] ?? null;
            if ($pid === null) {
                continue;
            }
            $activitiesByPatient[$pid][(int) $a->activity_id] = true;
            $allActivityIds[(int) $a->activity_id] = true;
        }
        if (empty($allActivityIds)) {
            return [];
        }

        // Aktuelle Anforderungen (Soll) je Tätigkeit.
        $reqByActivity = [];
        foreach (\Platform\Customer\Models\ActivityRequirement::query()->forTeam($teamId)->current()
                    ->whereIn('activity_id', array_keys($allActivityIds))
                    ->with('occasion')->get() as $r) {
            if (!$r->occasion_id) {
                continue;
            }
            $reqByActivity[(int) $r->activity_id][] = [
                'occasion_id' => (int) $r->occasion_id,
                'title'       => optional($r->occasion)->title,
                'care_type'   => $r->care_type,
            ];
        }

        $rows = [];
        foreach ($activitiesByPatient as $pid => $activityIds) {
            $e = $empByPatient[$pid] ?? null;
            if (!$e || !$e->patient) {
                continue;
            }
            $have = $provisionOccByPatient[$pid] ?? [];
            $seen = [];
            foreach (array_keys($activityIds) as $aid) {
                foreach ($reqByActivity[$aid] ?? [] as $req) {
                    $oid = $req['occasion_id'];
                    if (isset($have[$oid]) || isset($seen[$oid])) {
                        continue; // schon als Provision vorhanden oder bereits ergänzt
                    }
                    $seen[$oid] = true;
                    $rows[] = [
                        'patient_id'      => $pid,
                        'patient_name'    => $e->patient->getDisplayName() ?? ('Patient #' . $pid),
                        'position'        => $e->position ?: null,
                        'occasion'        => $req['title'],
                        'care_type'       => $req['care_type'],
                        'care_type_label' => CareType::tryFrom((string) $req['care_type'])?->label() ?? $req['care_type'],
                        'next_due'        => null,
                        'status'          => 'missing',
                        'url'             => $this->akteUrl($pid),
                    ];
                }
            }
        }

        return $rows;
    }

    protected function akteUrl(int $patientId): string
    {
        return Route::has('encounter.akte.show')
            ? route('encounter.akte.show', $patientId)
            : route('patient.patients.show', $patientId);
    }
}
