{{--
    Occupational · Beschäftigte-Liste — nx-Design-System.
--}}

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Beschäftigte" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Betriebsmedizin', 'route' => 'occupational.dashboard', 'icon' => 'briefcase'],
            ['label' => 'Beschäftigte'],
        ]">
            <x-nx-button variant="primary" size="sm" wire:click="$set('showCreate', true)">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neue:r Beschäftigte:r</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained" spacing="space-y-6">
        @if($employments->isEmpty())
            <x-nx-card>
                <x-nx-empty icon="heroicon-o-users">
                    Noch keine Beschäftigten. Lege den ersten über „Neue:r Beschäftigte:r" an.
                </x-nx-empty>
            </x-nx-card>
        @else
            <x-nx-card flush>
                <x-nx-table>
                    <x-nx-table-header>
                        <x-nx-table-header-cell>Name</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Betrieb</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Position</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Status</x-nx-table-header-cell>
                    </x-nx-table-header>
                    <x-nx-table-body>
                        @foreach($employments as $employment)
                            <x-nx-table-row wire:key="emp-{{ $employment->id }}"
                                            :href="route('occupational.employees.show', $employment->id)">
                                <x-nx-table-cell>{{ $employment->patient?->getDisplayName() ?? '—' }}</x-nx-table-cell>
                                <x-nx-table-cell>{{ $employment->organizationEntity?->name ?? '—' }}</x-nx-table-cell>
                                <x-nx-table-cell>{{ $employment->position ?? '—' }}</x-nx-table-cell>
                                <x-nx-table-cell>
                                    @if($employment->active)
                                        <x-nx-badge variant="success" dot>Aktiv</x-nx-badge>
                                    @else
                                        <x-nx-badge dot>Inaktiv</x-nx-badge>
                                    @endif
                                </x-nx-table-cell>
                            </x-nx-table-row>
                        @endforeach
                    </x-nx-table-body>
                </x-nx-table>
            </x-nx-card>
        @endif
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] mb-3">Beschäftigte</h3>
                    <div class="text-sm text-[color:var(--nx-muted)]">{{ $employments->count() }} Einträge.</div>
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

    {{-- Anlegen-Modal --}}
    <x-nx-modal wire:model="showCreate" size="md">
        <x-slot name="header">Neue:r Beschäftigte:r</x-slot>
        <div class="space-y-4">
            <x-nx-input-select name="patient_id" label="Patient" wire:model="patient_id"
                               :options="$patientOptions" nullable nullLabel="— Patient wählen —" required />
            <x-nx-input-text name="position" label="Position" wire:model="position" />
            <x-nx-input-select name="organization_entity_id" label="Betrieb" wire:model="organization_entity_id"
                               :options="$betriebOptions" nullable nullLabel="— Betrieb wählen —"
                               hint="Betrieb = Kunde aus dem Organization-Graphen." />
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-nx-button variant="ghost" wire:click="$set('showCreate', false)">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" wire:click="create">Anlegen</x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>
</x-ui-page>
