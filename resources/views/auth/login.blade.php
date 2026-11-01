@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <div class="card overflow-hidden">
        <div class="border-b border-line bg-gradient-to-b from-orange-soft/80 to-white px-6 py-7 text-center sm:px-8">
            @if (setting('logo_path'))
                <img src="{{ asset('storage/'.setting('logo_path')) }}" alt="{{ setting('business_name') }}" class="mx-auto h-14 w-14 rounded-xl object-cover shadow-sm">
            @else
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl bg-orange text-white shadow-sm">
                    <x-icon name="store" class="h-7 w-7" />
                </div>
            @endif
            <p class="mt-3 text-xs font-semibold uppercase tracking-widest text-orange-dark">{{ setting('business_name', 'Karinderya') }}</p>
            <h1 class="mt-1 font-display text-2xl font-bold text-ink">Welcome back</h1>
            <p class="mt-1 text-sm text-muted">Sign in to start your shift.</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="px-6 py-7 sm:px-8" x-data="{ submitting: false, showPassword: false }" @submit="submitting = true">
            @csrf

            @if ($errors->any())
                <div class="mb-5 flex items-start gap-2.5 rounded-lg border border-red-200 bg-red-50 px-3.5 py-3 text-sm text-red-700" role="alert">
                    <x-icon name="alert-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div>
                <label for="login" class="label">Email or username</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <x-icon name="user" class="h-4 w-4" />
                    </span>
                    <input
                        id="login"
                        name="login"
                        type="text"
                        value="{{ old('login') }}"
                        class="input pl-9 @error('login') input-error @enderror"
                        placeholder="you@karinderya.test"
                        autocomplete="username"
                        autofocus
                        required
                    >
                </div>
            </div>

            <div class="mt-4">
                <label for="password" class="label">Password</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <x-icon name="lock" class="h-4 w-4" />
                    </span>
                    <input
                        id="password"
                        name="password"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        class="input px-9 @error('password') input-error @enderror"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition hover:text-ink focus:outline-none focus-visible:text-orange"
                        @click="showPassword = !showPassword"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    >
                        <x-icon name="eye" class="h-4 w-4" x-show="!showPassword" />
                        <x-icon name="eye-off" class="h-4 w-4" x-show="showPassword" x-cloak />
                    </button>
                </div>
            </div>

            <label class="mt-5 flex cursor-pointer items-center gap-2.5 text-sm text-muted">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-line text-orange focus:ring-orange/30" @checked(old('remember'))>
                Keep me signed in on this device
            </label>

            <button type="submit" class="btn btn-primary btn-lg mt-6 w-full" :disabled="submitting">
                <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <x-icon name="log-in" class="h-4 w-4" x-show="!submitting" />
                <span x-text="submitting ? 'Signing in…' : 'Sign in'">Sign in</span>
            </button>
        </form>

        <div class="border-t border-line bg-slate-50/70 px-6 py-4 text-center">
            <p class="text-xs text-muted">Authorized staff only. Accounts are managed by an administrator.</p>
        </div>
    </div>

    <p class="mt-6 text-center text-xs text-muted">
        {{ setting('business_name', 'Karinderya POS') }} · Powered by Karinderya POS
    </p>
@endsection
