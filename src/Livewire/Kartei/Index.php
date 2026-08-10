<?php

namespace Platform\Occupational\Livewire\Kartei;

use Livewire\Component;
use Platform\Occupational\Company\OccupationalCompanyProvisionProvider;
use Platform\Occupational\Models\Employment;

/**
 * Vorsorgekartei — Soll/Ist-Matrix eines Betriebs (Beschäftigte × Anlässe × Status),
 * plus Perspektiven Zeitachse / Überfällig und Zell-Drill-in. Daten aus der bestehenden
 * Provision-/Soll-Logik (OccupationalCompanyProvisionProvider) — keine Befunde (Schweigepflicht).
 */
class Index extends Component
{
    public ?int $betrieb = null;          // organization_entity_id; null = alle
    public string $perspektive = 'matrix'; // matrix | zeit | ueber

    // Drill-in
    public ?int $cellPatient = null;
    public ?string $cellOccasion = null;

    public function mount(): void
    {
        $betriebe = $this->betriebe();
        if ($this->betrieb === null && !empty($betriebe)) {
            $this->betrieb = $betriebe[0]['id'];
        }
    }

    public function setPerspektive(string $p): void
    {
        $this->perspektive = in_array($p, ['matrix', 'zeit', 'ueber'], true) ? $p : 'matrix';
        $this->closeCell();
    }

    public function updatedBetrieb(): void
    {
        $this->betrieb = $this->betrieb !== null && $this->betrieb !== '' ? (int) $this->betrieb : null;
        $this->closeCell();
    }

    public function openCell(int $patientId, string $occasion): void
    {
        $this->cellPatient = $patientId;
        $this->cellOccasion = $occasion;
    }

    public function closeCell(): void
    {
        $this->cellPatient = null;
        $this->cellOccasion = null;
    }

    protected function teamId(): ?int
    {
        return auth()->user()?->currentTeam?->id;
    }

    /** Betriebe (Org-Entities) mit aktiven Beschäftigten + Name + Anzahl. */
    protected function betriebe(): array
    {
        $out = [];
        foreach (Employment::query()->forTeam($this->teamId())->where('active', true)
                    ->with('organizationEntity')->get() as $e) {
            $id = (int) $e->organization_entity_id;
            if (!$id) {
                continue;
            }
            $out[$id] ??= ['id' => $id, 'name' => $e->organizationEntity?->name ?: ('Betrieb #' . $id), 'count' => 0];
            $out[$id]['count']++;
        }
        usort($out, fn ($a, $b) => strcmp($a['name'], $b['name']));
        return $out;
    }

    /** Flache Zeilen (patient × anlass × status) aus der vorhandenen Soll/Ist-Logik. */
    protected function rows(array $betriebe): array
    {
        $ids = $this->betrieb ? [$this->betrieb] : array_map(fn ($b) => $b['id'], $betriebe);
        if (empty($ids)) {
            return [];
        }
        return (new OccupationalCompanyProvisionProvider())->provisionsFor($ids, (int) $this->teamId());
    }

    public function render()
    {
        $betriebe = $this->betriebe();
        $rows     = $this->rows($betriebe);

        // Pivot: Patienten (Zeilen), Anlässe (Spalten), Zellen[patient][anlass].
        $patients  = [];
        $occasions = [];
        $cells     = [];
        $counts    = ['ok' => 0, 'due' => 0, 'overdue' => 0, 'open' => 0, 'missing' => 0];

        foreach ($rows as $r) {
            $pid = (int) $r['patient_id'];
            $occ = $r['occasion'] ?: '—';
            $patients[$pid] ??= ['id' => $pid, 'name' => $r['patient_name'], 'position' => $r['position'], 'url' => $r['url']];
            $occasions[$occ] = true;
            $cells[$pid][$occ] = $r;
            if (isset($counts[$r['status']])) {
                $counts[$r['status']]++;
            }
        }

        uasort($patients, fn ($a, $b) => strcmp($a['name'], $b['name']));
        ksort($occasions);
        $occasions = array_keys($occasions);

        $total    = array_sum($counts);
        $aktuellP = $total > 0 ? (int) round($counts['ok'] / $total * 100) : 0;

        // Zeitachse-Buckets + Überfällig-Liste aus denselben Zeilen.
        $ueber = array_values(array_filter($rows, fn ($r) => $r['status'] === 'overdue'));
        usort($ueber, fn ($a, $b) => strcmp((string) $a['next_due'], (string) $b['next_due']));

        $buckets = [
            'overdue' => ['title' => 'Überfällig', 'crit' => true, 'rows' => []],
            'due'     => ['title' => 'Fällig ≤ 60 Tage', 'crit' => false, 'rows' => []],
            'gap'     => ['title' => 'Soll-Lücke / ohne Frist', 'crit' => false, 'rows' => []],
        ];
        foreach ($rows as $r) {
            if ($r['status'] === 'overdue') {
                $buckets['overdue']['rows'][] = $r;
            } elseif ($r['status'] === 'due') {
                $buckets['due']['rows'][] = $r;
            } elseif (in_array($r['status'], ['missing', 'open'], true)) {
                $buckets['gap']['rows'][] = $r;
            }
        }
        foreach (['overdue', 'due'] as $k) {
            usort($buckets[$k]['rows'], fn ($a, $b) => strcmp((string) $a['next_due'], (string) $b['next_due']));
        }

        // Drill-in-Detail
        $cellDetail = null;
        if ($this->cellPatient !== null && $this->cellOccasion !== null) {
            $cellDetail = $cells[$this->cellPatient][$this->cellOccasion] ?? null;
        }

        return view('occupational::livewire.kartei.index', [
            'betriebe'   => $betriebe,
            'patients'   => array_values($patients),
            'occasions'  => $occasions,
            'cells'      => $cells,
            'counts'     => $counts,
            'aktuellP'   => $aktuellP,
            'employees'  => count($patients),
            'ueber'      => $ueber,
            'buckets'    => $buckets,
            'cellDetail' => $cellDetail,
        ])->layout('platform::layouts.app');
    }
}
