@extends(auth()->user()->isAdmin() ? 'layouts.app' : 'layouts.pos')

@section('title', auth()->user()->isAdmin() ? 'Sales & transactions' : 'My sales')
@section('eyebrow', 'Transactions')

@section('content')
    @php
        $isAdmin = auth()->user()->isAdmin();
        $hasFilters = $filters['search'] !== ''
            || $filters['date_from']
            || $filters['date_to']
            || $filters['cashier'] !== ''
            || $filters['payment_method'] !== '';
        $exportQuery = array_filter([
            'search' => $filters['search'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'cashier' => $filters['cashier'],
            'payment_method' => $filters['payment_method'],
        ], fn ($value) => $value !== '' && $value !== null);
    @endphp

    <div class="{{ $isAdmin ? '' : 'mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8' }}">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="page-title">{{ $isAdmin ? 'Sales & transactions' : 'My sales' }}</h1>
                <p class="page-subtitle">
                    @if ($isAdmin)
                        Every completed sale, with the full record kept exactly as it was paid.
                    @else
                        Your completed sales. Open a transaction to reprint its receipt.
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($isAdmin)
                    <a href="{{ route('sales.export', $exportQuery) }}" class="btn btn-secondary">
                        <x-icon name="download" class="h-4 w-4" /> Export CSV
                    </a>
                @endif
                <a href="{{ route('pos.index') }}" class="btn btn-primary">
                    <x-icon name="shopping-cart" class="h-4 w-4" /> New sale
                </a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Transactions" :value="number_format($summary['transactions'])" icon="receipt-text" :hint="$hasFilters ? 'Matching these filters' : 'All completed sales'" />
            <x-stat-card label="Gross sales" :value="peso($summary['gross'])" icon="circle-dollar-sign" />
            <x-stat-card label="Discounts given" :value="peso($summary['discounts'])" icon="tag" />
            <x-stat-card label="Average sale" :value="peso($summary['average'])" icon="calculator" />
        </div>

        <form method="GET" action="{{ route('sales.index') }}" class="card mt-4 p-4">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                <div class="relative xl:col-span-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <x-icon name="search" class="h-4 w-4" />
                    </span>
                    <input type="search" name="search" value="{{ $filters['search'] }}" class="input pl-9" placeholder="Search transaction or reference">
                </div>

                <div>
                    <label for="date-from" class="sr-only">From date</label>
                    <input id="date-from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="input" aria-label="From date">
                </div>

                <div>
                    <label for="date-to" class="sr-only">To date</label>
                    <input id="date-to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input" aria-label="To date">
                </div>

                @if ($isAdmin)
                    <select name="cashier" class="input" aria-label="Filter by cashier">
                        <option value="">All cashiers</option>
                        @foreach ($cashiers as $cashier)
                            <option value="{{ $cashier->id }}" @selected($filters['cashier'] === (string) $cashier->id)>
                                {{ $cashier->name }}{{ $cashier->is_active ? '' : ' (inactive)' }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <select name="payment_method" class="input" aria-label="Filter by payment method">
                        <option value="">All payment methods</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected($filters['payment_method'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif

                <select name="sort" class="input" aria-label="Sort sales">
                    <option value="latest" @selected($filters['sort'] === 'latest')>Newest first</option>
                    <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option>
                    <option value="total_desc" @selected($filters['sort'] === 'total_desc')>Highest total</option>
                    <option value="total_asc" @selected($filters['sort'] === 'total_asc')>Lowest total</option>
                </select>

                @if ($isAdmin)
                    <select name="payment_method" class="input md:col-span-2 xl:col-span-1" aria-label="Filter by payment method">
                        <option value="">All payment methods</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected($filters['payment_method'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
                @if ($hasFilters)
                    <a href="{{ route('sales.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                @endif
                <button type="submit" class="btn btn-secondary btn-sm">
                    <x-icon name="list-filter" class="h-3.5 w-3.5" /> Apply filters
                </button>
            </div>
        </form>

        @if ($sales->isEmpty())
            <x-empty-state
                icon="receipt-text"
                title="{{ $hasFilters ? 'No sales match those filters' : 'No sales yet' }}"
                message="{{ $hasFilters ? 'Try widening the dates or clearing a filter.' : 'Completed sales from the POS will appear here automatically.' }}"
                :action="$hasFilters ? null : route('pos.index')"
                action-label="Start a sale"
            />
        @else
            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-muted">
                <p>Showing {{ $sales->firstItem() }}–{{ $sales->lastItem() }} of {{ $sales->total() }} transactions</p>
                <p>{{ $summary['transactions'] }} {{ \Illuminate\Support\Str::plural('transaction', $summary['transactions']) }} across the selected period</p>
            </div>

            <div class="card mt-2 overflow-hidden">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Transaction</th>
                                <th>Cashier</th>
                                <th>Items</th>
                                <th>Payment</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-right">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sales as $sale)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.show', $sale) }}" class="block font-semibold text-ink hover:text-orange-dark">
                                            {{ $sale->transaction_number }}
                                        </a>
                                        <p class="mt-0.5 text-xs text-muted">{{ $sale->sold_at->format('M j, Y · g:i A') }}</p>
                                    </td>
                                    <td>
                                        <span class="text-sm text-ink">{{ $sale->cashier?->name ?? 'Deleted user' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-sm text-muted">{{ $sale->items_count }} {{ \Illuminate\Support\Str::plural('item', $sale->items_count) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-neutral">{{ payment_method_label($sale->payment_method, $sale->payment_label) }}</span>
                                        @if ($sale->payment_reference)
                                            <p class="mt-1 max-w-36 truncate text-[11px] text-muted" title="{{ $sale->payment_reference }}">{{ $sale->payment_reference }}</p>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="num font-semibold text-ink">{{ peso($sale->total) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">Completed</span>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('receipts.show', $sale) }}" target="_blank" class="btn btn-ghost btn-sm" title="Open receipt">
                                                <x-icon name="receipt-text" class="h-3.5 w-3.5" /> Receipt
                                            </a>
                                            <a href="{{ route('sales.show', $sale) }}" class="btn btn-secondary btn-sm">View</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
@endsection
