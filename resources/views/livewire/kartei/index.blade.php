@php
    use Platform\Occupational\Enums\CareType;
    $statusMeta = [
        'ok'      => ['label' => 'aktuell',    'cls' => 'k-ok'],
        'due'     => ['label' => 'fällig',     'cls' => 'k-warn'],
        'overdue' => ['label' => 'überfällig', 'cls' => 'k-crit'],
        'open'    => ['label' => 'offen',      'cls' => 'k-open'],
        'missing' => ['label' => 'fehlt',      'cls' => 'k-miss'],
    ];
    $fmt = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('m/Y') : null;
@endphp

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Vorsorgekartei" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Arbeitsmedizin', 'route' => 'occupational.dashboard', 'icon' => 'briefcase'],
            ['label' => 'Vorsorgekartei'],
        ]" />
    </x-slot>

    <style>
        .vk-kpis{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}
        .vk-kpi{background:var(--nx-surface);border:1px solid var(--nx-line);border-radius:var(--nx-radius-lg);padding:11px 15px;min-width:104px}
        .vk-kpi .k{font-size:.64rem;text-transform:uppercase;letter-spacing:.09em;color:var(--nx-faint);font-weight:600}
        .vk-kpi .v{font-size:1.4rem;font-weight:680;margin-top:2px;font-variant-numeric:tabular-nums}
        .vk-kpi .v small{font-size:.85rem;color:var(--nx-muted);font-weight:600}
        .vk-kpi.crit .v{color:var(--nx-danger)} .vk-kpi.warn .v{color:var(--nx-warning)} .vk-kpi.ok .v{color:var(--nx-success)}
        .vk-tabs{display:flex;gap:2px;border-bottom:1px solid var(--nx-line);margin:6px 0 16px}
        .vk-tab{appearance:none;background:none;border:0;font:inherit;cursor:pointer;padding:10px 15px;margin-bottom:-1px;font-weight:600;color:var(--nx-faint);border-bottom:2px solid transparent}
        .vk-tab:hover{color:var(--nx-muted)} .vk-tab.on{color:var(--nx-text);border-bottom-color:var(--nx-text)}
        .vk-tab .b{font-variant-numeric:tabular-nums;font-size:.72rem;background:rgba(224,49,49,.12);color:var(--nx-danger);border-radius:999px;padding:1px 7px;font-weight:700;margin-left:6px}
        .vk-scroll{overflow-x:auto}
        table.vk{border-collapse:separate;border-spacing:0;width:100%;min-width:720px}
        table.vk th,table.vk td{padding:0;text-align:left}
        table.vk thead .ch{position:sticky;top:0;z-index:3;background:#faf9f6;border-bottom:1px solid var(--nx-line-strong);padding:9px 11px;vertical-align:bottom;white-space:nowrap;font-weight:640;font-size:.86rem}
        table.vk thead .cnr{position:sticky;left:0;top:0;z-index:5;background:#faf9f6;border-bottom:1px solid var(--nx-line-strong);border-right:1px solid var(--nx-line-strong);min-width:210px;padding:9px 13px;font-size:.64rem;text-transform:uppercase;letter-spacing:.09em;color:var(--nx-faint);font-weight:600}
        table.vk tbody .nc{position:sticky;left:0;z-index:2;background:var(--nx-surface);border-right:1px solid var(--nx-line-strong);border-bottom:1px solid var(--nx-line);padding:10px 13px;min-width:210px}
        table.vk tbody tr:hover .nc{background:var(--nx-hover)}
        .vk-nm{font-weight:640} .vk-role{color:var(--nx-muted);font-size:.8rem;margin-top:1px}
        table.vk td.c{border-bottom:1px solid var(--nx-line);border-right:1px solid var(--nx-line);padding:7px 9px}
        table.vk td.c.hit{cursor:pointer} table.vk td.c.hit:hover{background:var(--nx-hover)}
        .kchip{display:inline-flex;align-items:center;gap:6px;border-radius:var(--nx-radius);padding:5px 8px;font-size:.8rem;min-width:96px}
        .kchip .d{width:7px;height:7px;border-radius:50%;flex:none}
        .kchip .t{font-weight:600} .kchip .dt{font-variant-numeric:tabular-nums;font-size:.76rem;color:var(--nx-muted)}
        .k-ok{background:#e9f2ea}   .k-ok .d{background:#2b7a3f}   .k-ok .t{color:#2b7a3f}
        .k-warn{background:#f5ece0} .k-warn .d{background:#9c5518} .k-warn .t{color:#9c5518}
        .k-crit{background:#f7e8e6;border:1px solid rgba(175,42,42,.22)} .k-crit .d{background:#af2a2a} .k-crit .t{color:#af2a2a}
        .k-open{background:var(--nx-hover)} .k-open .d{background:var(--nx-faint)} .k-open .t{color:var(--nx-muted)}
        .k-miss{background:transparent;border:1px dashed #c79a5a} .k-miss .d{background:#9c5518} .k-miss .t{color:#9c5518}
        .k-none{color:var(--nx-faint);padding-left:10px}
        .vk-empty-day{color:var(--nx-faint);font-size:.82rem}
        .vk-li{display:flex;align-items:center;gap:14px;padding:12px 15px;border-bottom:1px solid var(--nx-line)}
        .vk-li:last-child{border-bottom:0} .vk-li:hover{background:var(--nx-hover)}
        .vk-li .who{min-width:180px} .vk-li .who .n{font-weight:640} .vk-li .who .r{font-size:.79rem;color:var(--nx-muted)}
        .vk-li .an{flex:1;font-weight:600} .vk-li .dd{font-variant-numeric:tabular-nums;font-size:.82rem;font-weight:700;white-space:nowrap}
        .vk-bhead{display:flex;align-items:center;gap:9px;margin:2px 2px 8px}
        .vk-bhead .t{font-weight:680} .vk-bhead.crit .t{color:var(--nx-danger)}
        .vk-bhead .c{font-variant-numeric:tabular-nums;font-size:.72rem;color:var(--nx-muted);background:var(--nx-active);border-radius:999px;padding:1px 8px;font-weight:600}
        .vk-legend{display:flex;flex-wrap:wrap;gap:16px;margin-top:14px;font-size:.8rem;color:var(--nx-muted)}
        .vk-legend .i{display:inline-flex;align-items:center;gap:6px} .vk-legend .d{width:8px;height:8px;border-radius:50%}
        .vk-loop{margin-top:12px;font-size:.8rem;color:var(--nx-faint)}
    </style>

    <x-ui-page-container width="full" spacing="space-y-4">

        {{-- Betrieb + KPIs --}}
        <div class="flex items-center gap-3 flex-wrap">
            <select wire:model.live="betrieb" class="text-sm font-semibold rounded-md border-[color:var(--nx-line-strong)] bg-[var(--nx-surface)]">
                <option value="">Alle Betriebe</option>
                @foreach ($betriebe as $b)
                    <option value="{{ $b['id'] }}">{{ $b['name'] }} ({{ $b['count'] }})</option>
                @endforeach
            </select>
            <span class="text-xs text-[color:var(--nx-faint)]">Soll aus der Tätigkeit, Ist aus der Vorsorge.</span>
        </div>

        <div class="vk-kpis">
            <div class="vk-kpi"><div class="k">Beschäftigte</div><div class="v">{{ $employees }}</div></div>
            <div class="vk-kpi ok"><div class="k">Aktuell</div><div class="v">{{ $aktuellP }}<small>%</small></div></div>
            <div class="vk-kpi warn"><div class="k">Fällig ≤ 60 Tage</div><div class="v">{{ $counts['due'] }}</div></div>
            <div class="vk-kpi crit"><div class="k">Überfällig</div><div class="v">{{ $counts['overdue'] }}</div></div>
        </div>

        {{-- Perspektiven --}}
        <div class="vk-tabs">
            <button class="vk-tab {{ $perspektive === 'matrix' ? 'on' : '' }}" wire:click="setPerspektive('matrix')">Matrix · Belegschaft</button>
            <button class="vk-tab {{ $perspektive === 'zeit' ? 'on' : '' }}" wire:click="setPerspektive('zeit')">Zeitachse</button>
            <button class="vk-tab {{ $perspektive === 'ueber' ? 'on' : '' }}" wire:click="setPerspektive('ueber')">Überfällig @if ($counts['overdue'])<span class="b">{{ $counts['overdue'] }}</span>@endif</button>
        </div>

        {{-- MATRIX --}}
        @if ($perspektive === 'matrix')
            <x-nx-card>
                @if (empty($patients) || empty($occasions))
                    <x-nx-empty icon="heroicon-o-table-cells">Keine Vorsorgen für diesen Betrieb. Sobald Tätigkeiten (Soll) oder Vorsorgen (Ist) gepflegt sind, füllt sich die Matrix.</x-nx-empty>
                @else
                    <div class="vk-scroll">
                        <table class="vk">
                            <thead><tr>
                                <th class="cnr">Beschäftigte:r · Tätigkeit</th>
                                @foreach ($occasions as $occ)
                                    <th class="ch">{{ $occ }}</th>
                                @endforeach
                            </tr></thead>
                            <tbody>
                                @foreach ($patients as $p)
                                    <tr>
                                        <td class="nc"><div class="vk-nm">{{ $p['name'] }}</div><div class="vk-role">{{ $p['position'] ?? '—' }}</div></td>
                                        @foreach ($occasions as $occ)
                                            @php $r = $cells[$p['id']][$occ] ?? null; @endphp
                                            @if ($r)
                                                @php $m = $statusMeta[$r['status']] ?? $statusMeta['open']; @endphp
                                                <td class="c hit" wire:click="openCell({{ $p['id'] }}, @js($occ))">
                                                    <span class="kchip {{ $m['cls'] }}"><span class="d"></span><span class="t">{{ $m['label'] }}</span>@if ($fmt($r['next_due']))<span class="dt">{{ $fmt($r['next_due']) }}</span>@endif</span>
                                                </td>
                                            @else
                                                <td class="c"><span class="k-none">–</span></td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-nx-card>
            <div class="vk-legend">
                <span class="i"><span class="d" style="background:#2b7a3f"></span>aktuell</span>
                <span class="i"><span class="d" style="background:#9c5518"></span>fällig ≤ 60 Tage</span>
                <span class="i"><span class="d" style="background:#af2a2a"></span>überfällig</span>
                <span class="i"><span class="d" style="background:var(--nx-faint)"></span>offen (ohne Frist)</span>
                <span class="i"><span class="d" style="border:1px dashed #c79a5a;background:transparent"></span>fehlt (Soll-Lücke)</span>
                <span class="i" style="margin-left:auto;color:var(--nx-faint)">Zelle anklicken für Details →</span>
            </div>
            <div class="vk-loop">↻ Geschlossener Loop: Tätigkeit setzt das Soll → Termin → Bescheinigung schreibt die nächste Frist zurück in die Kartei.</div>

        {{-- ZEITACHSE --}}
        @elseif ($perspektive === 'zeit')
            @foreach ($buckets as $key => $bk)
                @if (!empty($bk['rows']))
                    <div>
                        <div class="vk-bhead {{ $bk['crit'] ? 'crit' : '' }}"><span class="t">{{ $bk['title'] }}</span><span class="c">{{ count($bk['rows']) }}</span></div>
                        <x-nx-card>
                            @foreach ($bk['rows'] as $r)
                                @php $m = $statusMeta[$r['status']] ?? $statusMeta['open']; @endphp
                                <div class="vk-li">
                                    <div class="dd" style="min-width:78px;color:{{ $key === 'overdue' ? 'var(--nx-danger)' : 'var(--nx-text)' }}">{{ $fmt($r['next_due']) ?? '—' }}</div>
                                    <div class="who"><div class="n">{{ $r['patient_name'] }}</div><div class="r">{{ $r['position'] ?? '—' }}</div></div>
                                    <div class="an">{{ $r['occasion'] ?? '—' }} <span class="dt" style="color:var(--nx-faint);font-weight:400">· {{ $r['care_type_label'] ?? '' }}</span></div>
                                    <span class="kchip {{ $m['cls'] }}"><span class="d"></span><span class="t">{{ $m['label'] }}</span></span>
                                    <a href="{{ $r['url'] }}" class="text-sm font-semibold text-[color:var(--nx-text)] hover:underline">Akte →</a>
                                </div>
                            @endforeach
                        </x-nx-card>
                    </div>
                @endif
            @endforeach
            @if (empty($buckets['overdue']['rows']) && empty($buckets['due']['rows']) && empty($buckets['gap']['rows']))
                <x-nx-card><x-nx-empty icon="heroicon-o-calendar-days">Nichts fällig oder offen — alles im grünen Bereich.</x-nx-empty></x-nx-card>
            @endif
            <div class="vk-loop">↻ Dieselbe Kartei, nach Zeit geschnitten — die Grundlage für Terminplanung & Einladungen.</div>

        {{-- ÜBERFÄLLIG --}}
        @else
            <x-nx-card>
                @if (empty($ueber))
                    <x-nx-empty icon="heroicon-o-check-circle">Keine überfälligen Vorsorgen. 🎉</x-nx-empty>
                @else
                    @foreach ($ueber as $r)
                        <div class="vk-li">
                            <div class="who"><div class="n">{{ $r['patient_name'] }}</div><div class="r">{{ $r['position'] ?? '—' }}</div></div>
                            <div class="an">{{ $r['occasion'] ?? '—' }} <span class="dt" style="color:var(--nx-faint);font-weight:400">· {{ $r['care_type_label'] ?? '' }}</span></div>
                            <div class="dd" style="color:var(--nx-danger)">seit {{ $fmt($r['next_due']) ?? '—' }}</div>
                            <a href="{{ $r['url'] }}" class="text-sm font-semibold text-[color:var(--nx-text)] hover:underline">Akte →</a>
                        </div>
                    @endforeach
                @endif
            </x-nx-card>
            <div class="vk-loop">↻ „Akte" öffnet den Beschäftigten — nach der Vorsorge verschwindet die Zeile automatisch.</div>
        @endif

    </x-ui-page-container>

    {{-- Drill-in in der rechten Sidebar --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Detail" width="w-80" :defaultOpen="{{ $cellDetail ? 'true' : 'false' }}" storeKey="activityOpen" side="right">
            <div class="p-6 space-y-4">
                @if ($cellDetail)
                    @php $m = $statusMeta[$cellDetail['status']] ?? $statusMeta['open']; @endphp
                    <div>
                        <div class="text-base font-semibold text-[color:var(--nx-text)]">{{ $cellDetail['patient_name'] }}</div>
                        <div class="text-sm text-[color:var(--nx-muted)]">{{ $cellDetail['occasion'] ?? '—' }} · {{ $cellDetail['care_type_label'] ?? '' }}</div>
                    </div>
                    <span class="kchip {{ $m['cls'] }}"><span class="d"></span><span class="t">{{ $m['label'] }}</span></span>
                    <div class="grid grid-cols-1 gap-3">
                        <div><div class="text-xs uppercase tracking-wide text-[color:var(--nx-faint)] font-semibold">Nächste Frist</div>
                            <div class="font-semibold mt-0.5">{{ $cellDetail['next_due'] ? \Illuminate\Support\Carbon::parse($cellDetail['next_due'])->format('d.m.Y') : '—' }}</div></div>
                        <div><div class="text-xs uppercase tracking-wide text-[color:var(--nx-faint)] font-semibold">Vorsorgeart</div>
                            <div class="font-semibold mt-0.5">{{ $cellDetail['care_type_label'] ?? '—' }}</div></div>
                    </div>
                    <div class="text-xs text-[color:var(--nx-faint)] bg-[var(--nx-hover)] rounded-md p-2">🔒 Nur Anlass, Art und Frist — Befunde bleiben in der Akte (Schweigepflicht).</div>
                    <div class="flex gap-2">
                        <a href="{{ $cellDetail['url'] }}" class="px-3 py-1.5 text-sm rounded-md bg-[var(--nx-accent)] text-[color:var(--nx-on-accent)] font-semibold">Zur Akte</a>
                        <button wire:click="closeCell" class="px-3 py-1.5 text-sm rounded-md border border-[color:var(--nx-line-strong)]">Schließen</button>
                    </div>
                @else
                    <div class="text-sm text-[color:var(--nx-muted)]">Klicke in der Matrix auf eine Zelle, um Anlass, Frist und Status zu sehen.</div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
