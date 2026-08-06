{{--
    Occupational · Haupt-Sidebar (nx). Modul-Links + Betrieb-Baum als Kontext-Linse.
--}}

<div>
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--nx-text)] uppercase border-b border-[color:var(--nx-line)] mb-2">
        Betriebsmedizin
    </div>

    <x-ui-sidebar-list>
        <x-ui-sidebar-item :href="route('occupational.dashboard')" :active="request()->routeIs('occupational.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--nx-text)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('occupational.employees.index')" :active="request()->routeIs('occupational.employees.index') && ! request()->query('node')">
            @svg('heroicon-o-users', 'w-4 h-4 text-[var(--nx-text)]')
            <span class="ml-2 text-sm">Alle Beschäftigte</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    <x-ui-tree-nav label="Nach Betrieb" :nodes="$nodes" :activeId="$activeId" />

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
