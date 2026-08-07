<?php

namespace Platform\Occupational\Encounter;

use Platform\Encounter\Contracts\CertificateContextProvider;
use Platform\Occupational\Models\Employment;
use Platform\Occupational\Models\Provision;

/**
 * Liefert encounter den arbeitsmedizinischen Kontext einer Bescheinigung: den Arbeitgeber
 * (aktive Beschäftigung → Betrieb-Org-Entity) und die Vorsorge-Pflichten (Anlass/Art/Frist).
 * Graph-nativ und lose — kein FK aus encounter auf occupational.
 */
class OccupationalCertificateContext implements CertificateContextProvider
{
    public function contextFor(int $patientId, int $teamId): ?array
    {
        if ($patientId <= 0) {
            return null;
        }

        // Arbeitgeber = aktive Beschäftigung → Name der Betrieb-Org-Entity.
        $employer   = null;
        $employment = Employment::query()->forTeam($teamId)
            ->where('patient_id', $patientId)->active()
            ->with('organizationEntity')
            ->orderByDesc('started_at')
            ->first();

        if ($employment && $employment->organizationEntity) {
            $employer = [
                'name'      => $employment->organizationEntity->name,
                'entity_id' => (int) $employment->organization_entity_id,
                'address'   => null,
            ];
        }

        // Vorsorge-Pflichten (Anlass/Art/Frist) — Anlass-Titel per morphMap (arbmedvv).
        $provisions = Provision::query()->forTeam($teamId)
            ->where('patient_id', $patientId)
            ->with('occasion')
            ->get()
            ->map(fn (Provision $p) => [
                'occasion_id'    => $p->occasion_id ? (int) $p->occasion_id : null,
                'occasion_title' => optional($p->occasion)->title,
                'care_type'      => $p->type?->label(),
                'next_due'       => optional($p->next_due_at)->toDateString(),
            ])
            ->all();

        if (!$employer && empty($provisions)) {
            return null;
        }

        return ['employer' => $employer, 'provisions' => $provisions];
    }
}
