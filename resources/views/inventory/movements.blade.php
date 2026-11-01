@extends('layouts.app')

@section('title', 'Stock movements')
@section('eyebrow', 'Inventory')

@section('content')
    @php
        $typeLabels = [
            'stock_in' => 'Stock in',
            'stock_out' => 'Stock out',
            'adjustment' => 'Adjustment',
            'sale' => 'Sale',
        ];
        $typeClasses = [
            'stock_in' => 'badge-success',
            'stock_out' => 'badge-danger',
            'adjustment' => 'badge-warning',
            'sale' => 'badge-orange',
        ];
    @endphp

    <div class="mb-6">
        <a href="{{ route('inventory.index') }}" class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-ink">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back to inventory
        </a>
        <h1 class="page-title">Stock movements</h1>
        <p class="page-subtitle">Every stock in, stock out, count correction, and sale deduction.</p>
    </div>

    <form method="GET" action="{{ route('inventory.movements') }}" class="card mb-4 p-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div class="relative xl:col-span-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input type="search" name="search" value="{{ $filters['search'] }}" class="input pl-9" placeholder="Search product name or code">
            </div>
            <select name="type" class="input" aria-label="Movement type">
                <option value="">All movement types</option>
                @foreach ($typeLabels as $value => $label)
                    <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] }}" class="input" aria-label="From date">
            <input type="date" name="to" value="{{ $filters['to'] }}" class="input" aria-label="To date">
        </div>
        <div class="mt-3 flex items-center justify-end gap-2">
            <a href="{{ route('inventory.movements') }}" class="btn btn-ghost btn-sm">Reset</a>
            <button type="submit" class="btn btn-secondary btn-sm">
                <x-icon name="list-filter" class="h-3.5 w-3.5" /> Apply filters
            </button>
        </div>
    </form>

    @if ($movements->isEmpty())
        <x-empty-state icon="history" title="No stock movements found" message="Adjustments, deliveries, and completed sales will appear here." />
    @else
        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th class="text-right">Change</th>
                            <th class="text-right">Before → after</th>
                            <th>Reason</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            <tr>
                                <td class="whitespace-nowrap text-xs text-muted">{{ $movement->created_at->format('M j, Y g:i A') }}</td>
                                <td>
                                    <p class="max-w-48 truncate font-medium">{{ $movement->product?->name ?? 'Deleted product' }}</p>
                                    <p class="num text-xs text-muted">{{ $movement->product?->sku }}</p>
                                </td>
                                <td><span class="badge {{ $typeClasses[$movement->type] ?? 'badge-neutral' }}">{{ $typeLabels[$movement->type] ?? ucfirst($movement->type) }}</span></td>
                                <td class="num text-right font-semibold {{ (float) $movement->quantity_change > 0 ? 'text-success' : ((float) $movement->quantity_change < 0 ? 'text-danger' : 'text-muted') }}">
                                    {{ (float) $movement->quantity_change > 0 ? '+' : '' }}{{ quantity($movement->quantity_change) }}
                                </td>
                                <td class="num whitespace-nowrap text-right text-sm text-muted">{{ quantity($movement->quantity_before) }} → <span class="font-semibold text-ink">{{ quantity($movement->quantity_after) }}</span></td>
                                <td class="text-xs text-muted">
                                    @if ($movement->sale)
                                        Sale {{ $movement->sale->transaction_number }}
                                    @else
                                        {{ $movement->reason ?? '—' }}
                                    @endif
                                </td>
                                <td class="text-sm">{{ $movement->user?->name ?? 'System' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-line px-4 py-3">{{ $movements->links() }}</div>
        </div>
    @endif
@endsection
