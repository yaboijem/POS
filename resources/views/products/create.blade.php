@extends('layouts.app')

@section('title', 'Add product')
@section('eyebrow', 'Catalogue')

@section('content')
    <div class="mb-6">
        <a href="{{ route('products.index') }}" class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-ink">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back to products
        </a>
        <h1 class="page-title">Add product</h1>
        <p class="page-subtitle">Add a rice meal, viand, drink, or snack to your menu.</p>
    </div>

    @include('products._form', [
        'product' => $product,
        'categories' => $categories,
        'action' => route('products.store'),
        'method' => 'POST',
        'submitLabel' => 'Add product',
    ])
@endsection
