{{--
    Occupational · Betrieb/Abteilung-Detail — nx-Design-System.
--}}

<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$entity->name" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="array_values(array_filter([
            ['label' => 'Betriebe', 'route' => 'occupational.companies.index', 'icon' => 'building-office-2'],
            $parent ? ['label' => $parent->name] : null,
            ['label' => $entity->name],
        ]))" />
    </x-slot>

    <x-ui-page-container width="contained" spacing="space-y-6">
        <x-nx-card>
            <div class="flex items-center gap-3">
                @svg($parent ? 'heroicon-o-building-office' : 'heroicon-o-building-office-2', 'w-6 h-6 text-[color:var(--nx-muted)]')
                <div>
                    <div class="text-sm font-medium text-[color:var(--nx-text)]">{{ $entity->name }}</div>
                    <div class="text-xs text-[color:var(--nx-muted)]">{{ $entity->entityType?->name }}</div>
                </div>
            </div>
        </x-nx-card>

        {{-- Abteilungen --}}
        @if($children->isNotEmpty())
            <x-nx-section icon="heroicon-o-building-office" title="Abteilungen" :hint="$children->count()">
                <x-nx-card flush class="divide-y divide-[color:var(--nx-line)]">
                    @foreach($children as $child)
                        <a href="{{ route('occupational.companies.show', $child->id) }}" wire:navigate
                           class="flex items-center justify-between px-4 py-2.5 hover:bg-[color:var(--nx-hover)]">
                            <span class="flex items-center gap-2 min-w-0">
                                @svg('heroicon-o-building-office', 'w-4 h-4 text-[color:var(--nx-muted)]')
                                <span class="truncate text-[color:var(--nx-text)]">{{ $child->name }}</span>
                                <span class="text-xs text-[color:var(--nx-faint)]">{{ $child->entityType?->name }}</span>
                            </span>
                            <span class="flex items-center gap-1.5 text-xs text-[color:var(--nx-muted)] shrink-0">
                                @svg('heroicon-o-users', 'w-4 h-4') {{ (int) ($childCounts[$child->id] ?? 0) }}
                            </span>
                        </a>
                    @endforeach
                </x-nx-card>
            </x-nx-section>
        @endif

        {{-- Beschäftigte hier --}}
        <x-nx-section icon="heroicon-o-users" title="Beschäftigte" :hint="$employees->count()">
            @if($employees->isEmpty())
                <x-nx-card>
                    <x-nx-empty icon="heroicon-o-users">Keine Beschäftigten direkt an diesem Knoten.</x-nx-empty>
                </x-nx-card>
            @else
                <x-nx-card flush>
                    <x-nx-table>
                        <x-nx-table-header>
                            <x-nx-table-header-cell>Name</x-nx-table-header-cell>
                            <x-nx-table-header-cell>Position</x-nx-table-header-cell>
                            <x-nx-table-header-cell>Status</x-nx-table-header-cell>
                        </x-nx-table-header>
                        <x-nx-table-body>
                            @foreach($employees as $employment)
                                <x-nx-table-row wire:key="emp-{{ $employment->id }}"
                                                :href="route('occupational.employees.show', $employment->id)">
                                    <x-nx-table-cell>{{ $employment->patient?->getDisplayName() ?? '—' }}</x-nx-table-cell>
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
        </x-nx-section>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[color:var(--nx-faint)] mb-3">Betrieb</h3>
                    <div class="text-sm text-[color:var(--nx-text)]">{{ $entity->name }}</div>
                    <div class="text-sm text-[color:var(--nx-muted)]">{{ $entity->entityType?->name }}</div>
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
