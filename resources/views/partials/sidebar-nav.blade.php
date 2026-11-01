@php
    $admin = auth()->user()->isAdmin();

    $groups = $admin
        ? [
            'Workspace' => [
                ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'active' => 'dashboard'],
                ['route' => 'pos.index', 'label' => 'Point of Sale', 'icon' => 'shopping-cart', 'active' => 'pos.*'],
                ['route' => 'sales.index', 'label' => 'Sales & Transactions', 'icon' => 'receipt-text', 'active' => 'sales.*'],
            ],
            'Management' => [
                ['route' => 'products.index', 'label' => 'Products', 'icon' => 'package', 'active' => 'products.*'],
                ['route' => 'categories.index', 'label' => 'Categories', 'icon' => 'tags', 'active' => 'categories.*'],
                ['route' => 'inventory.index', 'label' => 'Inventory', 'icon' => 'boxes', 'active' => 'inventory.*'],
                ['route' => 'users.index', 'label' => 'Users', 'icon' => 'users', 'active' => 'users.*'],
                ['route' => 'reports.index', 'label' => 'Reports', 'icon' => 'chart-column', 'active' => 'reports.*'],
                ['route' => 'settings.index', 'label' => 'Settings', 'icon' => 'settings', 'active' => 'settings.*'],
                ['route' => 'audit.index', 'label' => 'Audit log', 'icon' => 'history', 'active' => 'audit.*'],
            ],
        ]
        : [
            'Workspace' => [
                ['route' => 'pos.index', 'label' => 'Point of Sale', 'icon' => 'shopping-cart', 'active' => 'pos.*'],
                ['route' => 'sales.index', 'label' => 'My Sales', 'icon' => 'receipt-text', 'active' => 'sales.*'],
            ],
        ];
@endphp

<nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="Main navigation">
    @foreach ($groups as $group => $items)
        @php($visible = collect($items)->filter(fn ($item) => \Illuminate\Support\Facades\Route::has($item['route'])))
        @if ($visible->isNotEmpty())
            <div>
                <p class="mb-2 px-3 text-xs font-medium text-muted" @if (empty($mobile)) x-show="$store.ui.sidebarOpen" @endif>{{ $group }}</p>
                <div class="space-y-1">
                    @foreach ($visible as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            class="nav-link {{ request()->routeIs($item['active']) ? 'nav-link-active' : '' }}"
                            title="{{ $item['label'] }}"
                            @if (request()->routeIs($item['active'])) aria-current="page" @endif
                            @if (! empty($mobile)) @click="mobileOpen = false" @endif
                        >
                            <x-icon :name="$item['icon']" class="h-4 w-4 shrink-0" />
                            <span class="truncate" @if (empty($mobile)) x-show="$store.ui.sidebarOpen" @endif>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</nav>

<div class="border-t border-line p-3">
    <div class="flex items-center gap-3 rounded-lg px-2 py-2">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-orange-soft text-sm font-bold text-orange-dark">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div class="min-w-0 flex-1" @if (empty($mobile)) x-show="$store.ui.sidebarOpen" @endif>
            <p class="truncate text-sm font-semibold text-ink">{{ auth()->user()->name }}</p>
            <p class="text-xs capitalize text-muted">{{ auth()->user()->role }}</p>
        </div>
    </div>
    <form method="POST" action="{{ route('logout') }}" class="mt-1">
        @csrf
        <button type="submit" class="nav-link w-full" title="Sign out">
            <x-icon name="log-out" class="h-5 w-5 shrink-0" />
            <span @if (empty($mobile)) x-show="$store.ui.sidebarOpen" @endif>Sign out</span>
        </button>
    </form>
</div>
