<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#c2410c">
    <title>@yield('title', 'Dashboard') · {{ setting('business_name', config('app.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-workspace min-h-screen bg-page font-sans text-ink antialiased" x-data="{ mobileOpen: false }">
    <a href="#main-content" class="skip-link">Skip to content</a>
    <div class="min-h-screen md:flex">
        <aside
            class="admin-sidebar sticky top-0 hidden h-screen shrink-0 flex-col border-r border-line transition-[width] duration-200 md:flex"
            :class="$store.ui.sidebarOpen ? 'md:w-60' : 'md:w-20'"
        >
            <div class="flex h-16 shrink-0 items-center gap-3 px-4">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                    @if (setting('logo_path'))
                        <img src="{{ asset('storage/'.setting('logo_path')) }}" alt="" class="h-9 w-9 shrink-0 rounded-lg object-cover">
                    @else
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orange text-white">
                            <x-icon name="store" class="h-5 w-5" />
                        </span>
                    @endif
                    <span class="min-w-0" x-show="$store.ui.sidebarOpen">
                        <span class="block truncate font-display text-sm font-bold leading-tight text-ink">{{ setting('business_name', 'Karinderya') }}</span>
                        <span class="mt-1 block text-xs text-muted">Admin workspace</span>
                    </span>
                </a>
            </div>

            @include('partials.sidebar-nav')
        </aside>

        <dialog
            x-cloak
            x-effect="mobileOpen ? $el.showModal() : $el.close()"
            class="fixed inset-0 m-0 h-full max-h-none w-full max-w-none border-0 bg-transparent p-0 text-ink md:hidden"
            aria-label="Navigation menu"
            @cancel.prevent="mobileOpen = false"
            @close="mobileOpen = false"
            @resize.window="if (window.innerWidth >= 768) mobileOpen = false"
        >
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="mobileOpen = false" aria-hidden="true"></div>
            <aside class="admin-sidebar absolute inset-y-0 left-0 flex w-72 max-w-[86%] flex-col shadow-xl">
                <div class="flex h-16 shrink-0 items-center justify-between border-b border-line px-4">
                    <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                        @if (setting('logo_path'))
                            <img src="{{ asset('storage/'.setting('logo_path')) }}" alt="" class="h-9 w-9 shrink-0 rounded-lg object-cover">
                        @else
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orange text-white">
                                <x-icon name="store" class="h-5 w-5" />
                            </span>
                        @endif
                        <span class="min-w-0">
                            <span class="block truncate font-display text-sm font-bold text-ink">{{ setting('business_name', 'Karinderya') }}</span>
                            <span class="mt-1 block text-xs text-muted">Admin workspace</span>
                        </span>
                    </a>
                    <button type="button" class="btn btn-ghost btn-sm" @click="mobileOpen = false" aria-label="Close menu">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>
                @include('partials.sidebar-nav', ['mobile' => true])
            </aside>
        </dialog>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex h-14 items-center gap-2 border-b border-line bg-white px-4 sm:gap-3 sm:px-6">
                <button type="button" class="btn btn-ghost btn-sm md:hidden" @click="mobileOpen = true" :aria-expanded="mobileOpen" aria-label="Open menu">
                    <x-icon name="menu" class="h-5 w-5" />
                </button>
                <button type="button" class="btn btn-ghost btn-sm hidden md:inline-flex" @click="$store.ui.toggleSidebar()" :aria-label="$store.ui.sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'">
                    <span x-show="$store.ui.sidebarOpen"><x-icon name="panel-left-close" class="h-4 w-4" /></span>
                    <span x-show="!$store.ui.sidebarOpen" x-cloak><x-icon name="panel-left-open" class="h-4 w-4" /></span>
                </button>

                <div class="min-w-0 border-l border-line pl-3">
                    <p class="truncate text-sm font-medium text-ink">@yield('title', 'Dashboard')</p>
                </div>

                <div class="ml-auto flex items-center gap-2 sm:gap-3">
                    <span class="hidden items-center gap-1.5 text-xs text-muted lg:flex">
                        <x-icon name="calendar" class="h-3.5 w-3.5" />
                        {{ now()->format('D, M j, Y') }}
                    </span>
                    <a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm">
                        <x-icon name="shopping-cart" class="h-3.5 w-3.5" />
                        <span>New sale</span>
                    </a>

                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                        <button type="button" class="flex items-center gap-2 rounded-lg p-1 transition hover:bg-slate-100" @click="open = !open" :aria-expanded="open" aria-haspopup="menu" aria-label="Account menu">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-soft text-xs font-bold text-orange-dark">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        </button>
                        <div x-cloak x-show="open" class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-line bg-white shadow-lg" role="menu">
                            <div class="border-b border-line px-4 py-3">
                                <p class="truncate text-sm font-semibold text-ink">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-muted">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="p-1.5">
                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('dashboard') }}" class="nav-link py-2 text-sm" role="menuitem">
                                        <x-icon name="layout-dashboard" class="h-4 w-4" /> Dashboard
                                    </a>
                                @endif
                                @if (\Illuminate\Support\Facades\Route::has('sales.index'))
                                    <a href="{{ route('sales.index') }}" class="nav-link py-2 text-sm" role="menuitem">
                                        <x-icon name="receipt-text" class="h-4 w-4" /> {{ auth()->user()->isAdmin() ? 'Transactions' : 'My sales' }}
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="nav-link w-full py-2 text-sm" role="menuitem">
                                        <x-icon name="log-out" class="h-4 w-4" /> Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main id="main-content" class="flex-1 px-4 pb-10 pt-6 sm:px-6 lg:px-8" tabindex="-1">
                <div class="mx-auto w-full max-w-7xl">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <x-toasts />
    <x-confirm-dialog />
</body>
</html>
