<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#F97316">
    <title>@yield('title', 'Point of Sale') · {{ setting('business_name', config('app.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-page font-sans text-ink antialiased">
    <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-line bg-white px-3 sm:px-5">
        <a href="{{ route('pos.index') }}" class="flex min-w-0 items-center gap-2.5">
            @if (setting('logo_path'))
                <img src="{{ asset('storage/'.setting('logo_path')) }}" alt="" class="h-9 w-9 shrink-0 rounded-lg object-cover">
            @else
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orange text-white">
                    <x-icon name="store" class="h-5 w-5" />
                </span>
            @endif
            <span class="min-w-0">
                <span class="block max-w-40 truncate font-display text-sm font-bold text-ink sm:max-w-64">{{ setting('business_name', 'Karinderya') }}</span>
                <span class="block text-[10px] font-semibold uppercase tracking-widest text-orange-dark">Point of Sale</span>
            </span>
        </a>

        <div class="ml-auto flex items-center gap-1.5 sm:gap-2.5">
            <div class="hidden text-right md:block">
                <p class="text-[10px] font-medium uppercase tracking-wide text-muted">{{ now()->format('D, M j, Y') }}</p>
                <p class="num text-xs font-semibold text-ink" x-data="{ time: '{{ now()->format('g:i A') }}' }" x-init="setInterval(() => time = new Intl.DateTimeFormat('en-PH', { hour: 'numeric', minute: '2-digit', hour12: true }).format(new Date()), 30000)" x-text="time"></p>
            </div>

            @if (auth()->user()->isAdmin())
                <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm" title="Admin dashboard">
                    <x-icon name="layout-dashboard" class="h-3.5 w-3.5" />
                    <span class="hidden lg:inline">Dashboard</span>
                </a>
            @endif

            @if (\Illuminate\Support\Facades\Route::has('sales.index'))
                <a href="{{ route('sales.index') }}" class="btn btn-secondary btn-sm" title="My sales">
                    <x-icon name="receipt-text" class="h-3.5 w-3.5" />
                    <span class="hidden lg:inline">My sales</span>
                </a>
            @endif

            <div class="flex items-center gap-2 rounded-full border border-line bg-white py-1 pl-1 pr-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-orange-soft text-[11px] font-bold text-orange-dark">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="hidden text-xs font-semibold text-ink sm:block">{{ auth()->user()->name }}</span>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm" title="Sign out">
                    <x-icon name="log-out" class="h-4 w-4" />
                    <span class="sr-only">Sign out</span>
                </button>
            </form>
        </div>
    </header>

    <main class="min-h-0 flex-1">
        @yield('content')
    </main>

    <x-toasts />
    <x-confirm-dialog />
</body>
</html>
