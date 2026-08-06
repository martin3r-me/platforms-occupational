{{--
    Occupational · Sidebar (nx-Design-System). Nur var(--nx-*) Tokens.
--}}

<div>
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--nx-text)] uppercase border-b border-[color:var(--nx-line)] mb-2">
        Betriebsmedizin
    </div>

    <x-ui-sidebar-list label="Betriebsmedizin">
        <x-ui-sidebar-item :href="route('occupational.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--nx-text)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('occupational.companies.index')">
            @svg('heroicon-o-building-office-2', 'w-4 h-4 text-[var(--nx-text)]')
            <span class="ml-2 text-sm">Betriebe</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('occupational.employees.index')">
            @svg('heroicon-o-users', 'w-4 h-4 text-[var(--nx-text)]')
            <span class="ml-2 text-sm">Beschäftigte</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    @if($employments->isNotEmpty())
        <x-ui-sidebar-list label="Zuletzt">
            @foreach($employments as $employment)
                <x-ui-sidebar-item :href="route('occupational.employees.show', $employment->id)">
                    @svg('heroicon-o-user', 'w-4 h-4 text-[var(--nx-text)]')
                    <span class="ml-2 text-sm truncate">{{ $employment->patient?->getDisplayName() ?? '—' }}</span>
                </x-ui-sidebar-item>
            @endforeach
        </x-ui-sidebar-list>
    @endif

    <div x-show="collapsed" class="px-2 py-2 border-b border-[color:var(--nx-line)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('occupational.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--nx-text)] hover:bg-[var(--nx-bg)]">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
            <a href="{{ route('occupational.employees.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--nx-text)] hover:bg-[var(--nx-bg)]">
                @svg('heroicon-o-users', 'w-5 h-5')
            </a>
        </div>
    </div>
</div>
