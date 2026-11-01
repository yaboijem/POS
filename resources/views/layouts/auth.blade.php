<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#F97316">
    <title>@yield('title', 'Sign in') · {{ setting('business_name', config('app.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-page font-sans text-ink antialiased">
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-orange"></div>
        <div class="pointer-events-none absolute -left-32 -top-40 h-96 w-96 rounded-full bg-orange-soft"></div>
        <div class="pointer-events-none absolute -bottom-48 -right-32 h-[28rem] w-[28rem] rounded-full bg-orange-soft/70"></div>

        <main class="relative w-full max-w-md">
            @yield('content')
        </main>
    </div>

    <x-toasts />
</body>
</html>
