{{--
    Occupational · Beschäftigte:r Detail/Bearbeiten — nx-Design-System.
--}}

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$employment->patient?->getDisplayName() ?? 'Beschäftigte:r'" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Beschäftigte', 'route' => 'occupational.employees.index', 'icon' => 'users'],
            ['label' => $employment->patient?->getDisplayName() ?? 'Beschäftigte:r'],
        ]">
            <x-nx-button variant="primary" size="sm" wire:click="save">
                @svg('heroicon-o-check', 'w-4 h-4')
                <span>Speichern</span>
            </x-nx-button>
            <x-nx-button variant="danger" size="sm" wire:click="delete"
                         wire:confirm="Dieses Beschäftigungsverhältnis wirklich löschen?">
                @svg('heroicon-o-trash', 'w-4 h-4')
                <span>Löschen</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained" spacing="space-y-6">
        {{-- Beschäftigung --}}
        <x-nx-section icon="heroicon-o-briefcase" title="Beschäftigung">
            <x-nx-card>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-nx-input-text name="form.position" label="Position" wire:model="form.position" />
                    <x-nx-input-text name="form.personnel_number" label="Personalnummer" wire:model="form.personnel_number" />
                    <x-nx-input-select name="form.organization_entity_id" label="Betrieb / Abteilung" wire:model="form.organization_entity_id"
                                       :options="$betriebOptions" nullable nullLabel="— Betrieb/Abteilung wählen —"
                                       hint="Kunde oder Abteilung aus dem Organization-Graphen (Umwelt)." />
                    <x-nx-input-date name="form.started_at" label="Beginn" wire:model="form.started_at" />
                    <x-nx-input-date name="form.ended_at" label="Ende" wire:model="form.ended_at" />
                    <x-nx-input-checkbox name="form.active" label="Aktiv" wire:model="form.active" />
                    <x-nx-input-checkbox name="form.first_aider" label="Ersthelfer" wire:model="form.first_aider" />
                </div>
            </x-nx-card>
        </x-nx-section>

        {{-- Vorsorge (ArbMedVV) --}}
        <x-nx-section icon="heroicon-o-shield-check" title="Vorsorge" :hint="$provisions->count()">
            <x-slot name="action">
                <x-nx-button variant="secondary" size="sm" wire:click="$set('showProvisionModal', true)">
                    @svg('heroicon-o-plus', 'w-4 h-4') Vorsorge anlegen
                </x-nx-button>
            </x-slot>
            @if($provisions->isEmpty())
                <x-nx-card>
                    <x-nx-empty icon="heroicon-o-shield-check">
                        Noch keine Vorsorge. „Vorsorge anlegen" verknüpft einen ArbMedVV-Anlass mit Frist.
                    </x-nx-empty>
                </x-nx-card>
            @else
                <x-nx-card flush>
                    <x-nx-table>
                        <x-nx-table-header>
                            <x-nx-table-header-cell>Anlass</x-nx-table-header-cell>
                            <x-nx-table-header-cell>Art</x-nx-table-header-cell>
                            <x-nx-table-header-cell>Fällig</x-nx-table-header-cell>
                        </x-nx-table-header>
                        <x-nx-table-body>
                            @foreach($provisions as $provision)
                                <x-nx-table-row wire:key="prov-{{ $provision->id }}">
                                    <x-nx-table-cell>{{ $provision->occasion?->title ?? '—' }}</x-nx-table-cell>
                                    <x-nx-table-cell>{{ $provision->type?->label() ?? '—' }}</x-nx-table-cell>
                                    <x-nx-table-cell>
                                        @if($provision->next_due_at)
                                            <x-nx-badge :variant="$provision->isOverdue() ? 'danger' : 'default'" dot>
                                                {{ $provision->next_due_at->format('d.m.Y') }}
                                            </x-nx-badge>
                                        @else
                                            —
                                        @endif
                                    </x-nx-table-cell>
                                </x-nx-table-row>
                            @endforeach
                        </x-nx-table-body>
                    </x-nx-table>
                </x-nx-card>
            @endif
        </x-nx-section>

        {{-- Hinweise --}}
        <x-nx-section icon="heroicon-o-document-text" title="Hinweise">
            <x-nx-card>
                <div class="space-y-4">
                    <x-nx-input-textarea name="form.work_notes" label="Arbeitshinweise" wire:model="form.work_notes" rows="3" />
                    <x-nx-input-textarea name="form.risk" label="Risiko/Belastung" wire:model="form.risk" rows="3" />
                </div>
            </x-nx-card>
        </x-nx-section>
    </x-ui-page-container>

    {{-- Vorsorge anlegen --}}
    <x-nx-modal wire:model="showProvisionModal" size="md">
        <x-slot name="header">Vorsorge anlegen</x-slot>
        <div class="space-y-4">
            <x-nx-input-select name="provisionForm.occasion_id" label="ArbMedVV-Anlass" wire:model="provisionForm.occasion_id"
                               :options="$occasionOptions" nullable nullLabel="— Anlass wählen —" />
            <x-nx-input-select name="provisionForm.type" label="Vorsorgeart" wire:model="provisionForm.type"
                               :options="$careTypeOptions" />
            <div class="grid grid-cols-2 gap-4">
                <x-nx-input-text name="provisionForm.interval_months" label="Intervall (Monate)" wire:model="provisionForm.interval_months" type="number" />
                <x-nx-input-date name="provisionForm.next_due_at" label="Fällig am" wire:model="provisionForm.next_due_at" />
            </div>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-nx-button variant="ghost" wire:click="$set('showProvisionModal', false)">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" wire:click="createProvision">Anlegen</x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] mb-3">Patient</h3>
                    @if($employment->patient)
                        <a href="{{ route('patient.patients.show', $employment->patient->id) }}" wire:navigate
                           class="text-sm text-[color:var(--nx-accent)] hover:underline">
                            {{ $employment->patient->getDisplayName() }}
                        </a>
                    @else
                        <div class="text-sm text-[color:var(--nx-muted)]">—</div>
                    @endif
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
