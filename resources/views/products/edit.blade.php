@extends('layouts.app')

@section('title', 'Edit product')
@section('eyebrow', 'Catalogue')

@section('content')
    <div class="mb-6">
        <a href="{{ route('products.index') }}" class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-ink">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back to products
        </a>
        <h1 class="page-title">Edit {{ $product->name }}</h1>
        <p class="page-subtitle">Changes to price and availability take effect on the POS immediately.</p>
    </div>

    @include('products._form', [
        'product' => $product,
        'categories' => $categories,
        'action' => route('products.update', $product),
        'method' => 'PUT',
        'submitLabel' => 'Save changes',
    ])

    <div class="card mt-6 overflow-hidden">
        <div class="border-b border-line px-5 py-4">
            <h2 class="section-title">Price history</h2>
            <p class="mt-0.5 text-xs text-muted">The last 10 price changes for this product. Older completed sales are never changed.</p>
        </div>
        @if ($priceHistories->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-muted">No price changes recorded yet.</div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Changed</th>
                            <th>Old price</th>
                            <th>New price</th>
                            <th>By</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($priceHistories as $history)
                            <tr>
                                <td class="whitespace-nowrap text-xs text-muted">{{ $history->created_at->format('M j, Y g:i A') }}</td>
                                <td class="num text-muted">{{ peso($history->old_selling_price) }}</td>
                                <td class="num font-semibold">{{ peso($history->new_selling_price) }}</td>
                                <td class="text-sm">{{ $history->changer?->name ?? 'System' }}</td>
                                <td class="text-xs text-muted">{{ $history->note ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
