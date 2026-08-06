<?php

namespace Platform\Occupational\Patient;

use Platform\Patient\Contracts\PatientPanelProvider;
use Platform\Occupational\Models\Provision;
use Platform\Occupational\Models\Employment;

/**
 * Steuert das „Vorsorge"-Panel zur Patienten-Akte bei (ArbMedVV-Pflichten der Person).
 * Liefert NULL ohne Vorsorge. Verlinkt zur Verwaltung im occupational-Employee-Detail.
 */
class OccupationalVorsorgePanel implements PatientPanelProvider
{
    public function panel(int $patientId, int $teamId): ?array
    {
        $provisions = Provision::query()
            ->forTeam($teamId)
            ->where('patient_id', $patientId)
            ->with('occasion')
            ->orderByRaw('next_due_at is null, next_due_at asc')
            ->get();

        if ($provisions->isEmpty()) {
            return null;
        }

        $employment = Employment::query()
            ->forTeam($teamId)
            ->where('patient_id', $patientId)
            ->orderByDesc('active')
            ->orderByDesc('id')
            ->first();

        $manageUrl = $employment
            ? route('occupational.employees.show', $employment->id)
            : route('occupational.employees.index');

        $items = $provisions->map(function (Provision $p) use ($manageUrl) {
            return [
                'title'    => $p->occasion?->title ?? ($p->type?->label() ?? 'Vorsorge'),
                'subtitle' => $p->type?->label(),
                'meta'     => $p->next_due_at
                    ? ($p->isOverdue() ? 'überfällig · ' : 'fällig ') . $p->next_due_at->format('d.m.Y')
                    : null,
                'url'      => $manageUrl,
            ];
        })->all();

        return [
            'key'     => 'provision',
            'title'   => 'Vorsorge',
            'icon'    => 'shield-check',
            'order'   => 15,
            'items'   => $items,
            'actions' => [['label' => 'Vorsorge verwalten', 'url' => $manageUrl]],
            'empty'   => 'Keine Vorsorge',
        ];
    }
}
