{{--
    Occupational · Dashboard — nx-Design-System.
    Shell bleibt x-ui-page*, Inhalt ausschließlich x-nx-* + var(--nx-*).
--}}

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Arbeitsmedizin" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Arbeitsmedizin', 'icon' => 'briefcase'],
        ]">
            <x-nx-button variant="primary" size="sm" :href="route('occupational.employees.index')" wire:navigate>
                @svg('heroicon-o-users', 'w-4 h-4')
                <span>Zu den Beschäftigten</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained" spacing="space-y-6">
        <p class="text-sm text-[color:var(--nx-muted)]">
            Die betriebsärztliche Sicht je Beschäftigtem — Vorsorge & Beschäftigung. <strong>Dieselben Personen</strong> wie in <em>Patienten</em>, hier arbeitsmedizinisch statt als Stammdaten.
        </p>

        <x-nx-stat-grid :cols="2">
            <x-nx-stat label="Betriebe" :value="$stats['companies']" icon="heroicon-o-building-office-2" hint="mit Beschäftigten" />
            <a href="{{ route('occupational.employees.index') }}" wire:navigate>
                <x-nx-stat label="Beschäftigte" :value="$stats['employees']" icon="heroicon-o-users" hint="gesamt" />
            </a>
        </x-nx-stat-grid>

        @if($stats['employees'] === 0)
            <x-nx-card>
                <x-nx-empty icon="heroicon-o-briefcase">
                    Noch keine Beschäftigten. Lege den ersten in der Beschäftigten-Liste an.
                    <x-slot name="action">
                        <x-nx-button variant="secondary" size="sm" :href="route('occupational.employees.index')" wire:navigate>
                            Zu den Beschäftigten
                        </x-nx-button>
                    </x-slot>
                </x-nx-empty>
            </x-nx-card>
        @endif
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] mb-3">Betriebe</h3>
                    <div class="text-sm text-[color:var(--nx-muted)]">Wähle links einen Betrieb — oder öffne die Beschäftigten-Liste.</div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] mb-3">Letzte Aktivitäten</h3>
                    <div class="text-sm text-[color:var(--nx-muted)]">Keine Aktivitäten verfügbar.</div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
