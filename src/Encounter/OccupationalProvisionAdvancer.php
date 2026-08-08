<?php

namespace Platform\Occupational\Encounter;

use Illuminate\Support\Carbon;
use Platform\Encounter\Contracts\CertificateLifecycleListener;
use Platform\Occupational\Enums\CareType;
use Platform\Occupational\Models\Provision;

/**
 * Schreibt die Vorsorge-Kartei fort, sobald encounter eine Bescheinigung ausgestellt hat
 * (§ 3 Abs. 4 ArbMedVV — die Vorsorge gilt als durchgeführt, wenn bescheinigt):
 *
 *   last_done_at = Tag der Vorsorge (Termindatum)
 *   next_due_at  = last_done_at + interval_months   (bzw. null, wenn keine Regelfrist)
 *   last_certificate_id = die dokumentierende Bescheinigung
 *
 * Findet keine passende Provision, wird eine angelegt — so „lernt" die Kartei auch spontan
 * durchgeführte (z.B. Wunsch-)Vorsorgen. Idempotent: mehrere Bescheinigungen desselben
 * Termins (Patient + Arbeitgeber) führen zum selben Zustand.
 */
class OccupationalProvisionAdvancer implements CertificateLifecycleListener
{
    public function certificateIssued(array $event): void
    {
        // Nur Vorsorgeanlässe (arbmedvv) schreiben die Kartei fort.
        if (($event['occasion_type'] ?? null) !== 'arbmedvv_occasion') {
            return;
        }
        $occasionId = (int) ($event['occasion_id'] ?? 0);
        $patientId  = (int) ($event['patient_id'] ?? 0);
        $teamId     = (int) ($event['team_id'] ?? 0);
        if ($occasionId <= 0 || $patientId <= 0 || $teamId <= 0) {
            return;
        }

        $examinedOn = !empty($event['examined_on'])
            ? Carbon::parse($event['examined_on'])->startOfDay()
            : Carbon::now()->startOfDay();

        $provision = Provision::query()->forTeam($teamId)
            ->where('patient_id', $patientId)
            ->where('occasion_type', 'arbmedvv_occasion')
            ->where('occasion_id', $occasionId)
            ->first();

        if (!$provision) {
            $provision = new Provision([
                'team_id'       => $teamId,
                'patient_id'    => $patientId,
                'occasion_type' => 'arbmedvv_occasion',
                'occasion_id'   => $occasionId,
                'type'          => $this->careTypeFromOccasion($occasionId, $teamId),
            ]);
        }

        $interval = (int) ($provision->interval_months ?? 0);

        $provision->last_done_at        = $examinedOn;
        $provision->next_due_at         = $interval > 0 ? $examinedOn->copy()->addMonths($interval) : null;
        $provision->last_certificate_id = (int) ($event['certificate_id'] ?? 0) ?: null;
        $provision->save();
    }

    /**
     * Vorsorgeart aus dem Katalog-Anlass (arbmedvv) übernehmen — gleicher Werteraum
     * (mandatory|offered|request|follow_up). Guarded; Fallback = Pflichtvorsorge.
     */
    protected function careTypeFromOccasion(int $occasionId, int $teamId): string
    {
        if (class_exists(\Platform\Arbmedvv\Models\Occasion::class)) {
            $value = \Platform\Arbmedvv\Models\Occasion::query()
                ->where('team_id', $teamId)->whereKey($occasionId)->value('care_type');
            if (is_string($value) && $value !== '' && CareType::tryFrom($value)) {
                return $value;
            }
        }

        return CareType::Mandatory->value;
    }
}
