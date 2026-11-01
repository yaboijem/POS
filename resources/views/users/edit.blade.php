@extends('layouts.app')

@section('title', 'Edit '.$user->name)
@section('eyebrow', 'Staff')

@section('content')
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-ink">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back to users
        </a>
        <h1 class="page-title">Edit {{ $user->name }}</h1>
        <p class="page-subtitle">Update account details, role, or access. Password changes are handled separately.</p>
    </div>

    <div class="max-w-3xl">
        @include('users._form', ['user' => $user])

        <div id="reset-password" class="card card-pad mt-4">
            <h2 class="section-title">Reset password</h2>
            <p class="mt-1 text-sm text-muted">Set a new password for this staff member. You do not need to know the old password.</p>

            <form method="POST" action="{{ route('users.password', $user) }}" class="mt-4 max-w-md" x-data="{ show: false }">
                @csrf
                @method('PATCH')
                <div class="space-y-3">
                    <div>
                        <label for="new-password" class="label">New password</label>
                        <input id="new-password" name="password" :type="show ? 'text' : 'password'" class="input" required minlength="8" autocomplete="new-password">
                    </div>
                    <div>
                        <label for="new-password-confirmation" class="label">Confirm new password</label>
                        <input id="new-password-confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" class="input" required minlength="8" autocomplete="new-password">
                    </div>
                    <label class="flex items-center gap-2 text-xs text-muted">
                        <input type="checkbox" x-model="show" class="h-4 w-4 rounded border-line text-orange focus:ring-orange">
                        Show passwords
                    </label>
                    @error('password')
                        <p class="text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn btn-secondary mt-4">
                    <x-icon name="key-round" class="h-4 w-4" /> Reset password
                </button>
            </form>
        </div>
    </div>
@endsection
