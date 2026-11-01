@extends('layouts.app')

@section('title', 'Edit category')
@section('eyebrow', 'Products')

@section('content')
    <div class="mb-6">
        <a href="{{ route('categories.index') }}" class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-ink">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back to categories
        </a>
        <h1 class="page-title">Edit {{ $category->name }}</h1>
        <p class="page-subtitle">Changes appear on the POS on the next page load.</p>
    </div>

    <div class="card card-pad max-w-2xl">
        @include('categories._form', [
            'category' => $category,
            'action' => route('categories.update', $category),
            'method' => 'PUT',
            'submitLabel' => 'Save changes',
        ])
    </div>

    <div class="mt-4 max-w-2xl rounded-lg border border-line bg-white px-4 py-3 text-xs text-muted">
        <span class="font-semibold text-ink">{{ $category->products()->withTrashed()->count() }}</span> products use this category. Deleting it is blocked until they are reassigned.
    </div>
@endsection
