@extends('layouts.app')

@section('title', 'Add category')
@section('eyebrow', 'Products')

@section('content')
    <div class="mb-6">
        <a href="{{ route('categories.index') }}" class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-ink">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back to categories
        </a>
        <h1 class="page-title">Add category</h1>
        <p class="page-subtitle">Create a group for similar products. You can reorder it later.</p>
    </div>

    <div class="card card-pad max-w-2xl">
        @include('categories._form', [
            'category' => $category,
            'action' => route('categories.store'),
            'method' => 'POST',
            'submitLabel' => 'Add category',
        ])
    </div>
@endsection
