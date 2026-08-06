<?php

namespace Platform\Occupational\Journal;

use Platform\Encounter\Contracts\JournalEntryProvider;
use Platform\Occupational\Models\Provision;
use Platform\Occupational\Models\Employment;

/**
 * Liefert Vorsorge-Pflichten und Beschäftigungen eines Patienten als Verlauf-Einträge
 * in die Akte (encounter). occupational → encounter (Fachmodul sitzt auf dem Kern).
 */
class OccupationalJournalProvider implements JournalEntryProvider
{
    public function entriesFor(int $patientId, int $teamId): array
    {
        $entries = [];

        $provisions = Provision::query()
            ->forTeam($teamId)
            ->where('patient_id', $patientId)
            ->with('occasion')
            ->get();

        foreach ($provisions as $p) {
            $entries[] = [
                'date'     => $p->next_due_at ?? $p->created_at,
                'anchor'   => 'prov-' . $p->id,
                'type'     => 'provision',
                'icon'     => 'heroicon-o-shield-check',
                'title'    => 'Vorsorge: ' . ($p->occasion?->title ?? ($p->type?->label() ?? '—')),
                'subtitle' => $p->type?->label(),
                'badge'    => $p->next_due_at
                    ? ['label' => ($p->isOverdue() ? 'überfällig ' : 'fällig ') . $p->next_due_at->format('d.m.Y'), 'variant' => $p->isOverdue() ? 'danger' : 'default']
                    : null,
                'lines'    => [],
                'url'      => null,
            ];
        }

        $employments = Employment::query()
            ->forTeam($teamId)
            ->where('patient_id', $patientId)
            ->with('organizationEntity')
            ->get();

        foreach ($employments as $e) {
            $entries[] = [
                'date'     => $e->started_at ?? $e->created_at,
                'anchor'   => 'emp-' . $e->id,
                'type'     => 'employment',
                'icon'     => 'heroicon-o-briefcase',
                'title'    => 'Beschäftigung: ' . ($e->position ?: '—') . ($e->organizationEntity ? ' @ ' . $e->organizationEntity->name : ''),
                'subtitle' => $e->active ? 'aktiv' : 'inaktiv',
                'badge'    => null,
                'lines'    => [],
                'url'      => route('occupational.employees.show', $e->id),
            ];
        }

        return $entries;
    }
}
