@extends('layouts.app')

@section('title', 'Add staff member')
@section('eyebrow', 'Staff')

@section('content')
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-ink">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back to users
        </a>
        <h1 class="page-title">Add staff member</h1>
        <p class="page-subtitle">Create an account for a cashier or another administrator.</p>
    </div>

    <div class="max-w-3xl">
        @include('users._form', ['user' => $user])
    </div>
@endsection
