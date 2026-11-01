@extends('layouts.app')

@section('title', 'Reports')
@section('eyebrow', 'Sales reporting')

@section('content')
    @php
        $hasSales = $summary['transactions'] > 0;
        $rangeLabel = $from->isSameDay($to)
            ? $from->format('F j, Y')
            : $from->format('M j, Y').' – '.$to->format('M j, Y');
        $quickRanges = [
            'Today' => [now()->toDateString(), now()->toDateString()],
            'Last 7 days' => [now()->subDays(6)->toDateString(), now()->toDateString()],
            'This month' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'Last 30 days' => [now()->subDays(29)->toDateString(), now()->toDateString()],
        ];
        $paymentRows = $payments->map(fn ($payment) => (object) [
            'name' => payment_method_label($payment->payment_method),
            'total' => (float) $payment->total,
            'transactions' => $payment->transactions,
        ]);
        $groupLabels = ['day' => 'Daily', 'week' => 'Weekly', 'month' => 'Monthly'];
        $exportQuery = ['from' => $from->toDateString(), 'to' => $to->toDateString()];
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Sales report</h1>
            <p class="page-subtitle">{{ $rangeLabel }} · grouped {{ strtolower($groupLabels[$grouping] ?? 'daily') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('reports.export', $exportQuery) }}" class="btn btn-secondary">
                <x-icon name="download" class="h-4 w-4" /> Export CSV
            </a>
            <a href="{{ route('sales.index') }}" class="btn btn-ghost">
                <x-icon name="receipt-text" class="h-4 w-4" /> View transactions
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-danger">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('reports.index') }}" class="card p-4">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div>
                <label for="from" class="label">From</label>
                <input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="input">
            </div>
            <div>
                <label for="to" class="label">To</label>
                <input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="input">
            </div>
            <div>
                <label for="group" class="label">Group trend</label>
                <select id="group" name="group" class="input">
                    @foreach ($groupLabels as $value => $label)
                        <option value="{{ $value }}" @selected($grouping === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">
                    <x-icon name="list-filter" class="h-4 w-4" /> Apply
                </button>
            </div>
            <div class="flex flex-wrap items-end gap-1.5 sm:col-span-2 xl:col-span-1 xl:justify-end">
                @foreach ($quickRanges as $label => [$rangeFrom, $rangeTo])
                    @php($active = $from->toDateString() === $rangeFrom && $to->toDateString() === $rangeTo)
                    <a
                        href="{{ route('reports.index', ['from' => $rangeFrom, 'to' => $rangeTo, 'group' => $grouping]) }}"
                        class="rounded-full border px-2.5 py-1 text-[11px] font-semibold transition {{ $active ? 'border-orange bg-orange-soft text-orange-dark' : 'border-line bg-white text-muted hover:border-orange/40 hover:text-ink' }}"
                    >{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </form>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Gross sales" :value="peso($summary['gross'])" icon="circle-dollar-sign" :hint="'Net of tax: '.peso($summary['net'])" />
        <x-stat-card label="Transactions" :value="number_format($summary['transactions'])" icon="receipt-text" hint="Completed sales" />
        <x-stat-card label="Average sale" :value="peso($summary['average'])" icon="calculator" :hint="'Discounts: '.peso($summary['discounts'])" />
        <x-stat-card label="Items sold" :value="quantity($summary['items'])" icon="package" :hint="'Tax recorded: '.peso($summary['tax'])" />
    </div>

    <div class="card mt-4 card-pad">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="section-title">Sales trend</h2>
                <p class="mt-0.5 text-xs text-muted">Gross sales for {{ $rangeLabel }}, {{ strtolower($groupLabels[$grouping]) }}.</p>
            </div>
            <div class="inline-flex rounded-lg border border-line bg-white p-1" role="group" aria-label="Report grouping">
                @foreach ($groupLabels as $value => $label)
                    <a
                        href="{{ route('reports.index', ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'group' => $value]) }}"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $grouping === $value ? 'bg-orange text-white shadow-sm' : 'text-muted hover:bg-slate-50 hover:text-ink' }}"
                    >{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="mt-4">
            @if (array_sum($series['values']) > 0)
                <x-line-chart :labels="$series['labels']" :values="$series['values']" :height="280" />
            @else
                <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-line bg-slate-50/50 px-6 py-12 text-center">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-orange-soft text-orange-dark">
                        <x-icon name="chart-column" class="h-5 w-5" />
                    </span>
                    <h3 class="mt-3 font-display text-base font-semibold text-ink">No sales in this period</h3>
                    <p class="mt-1 max-w-sm text-sm text-muted">Try a wider date range or complete a sale at the POS. The report updates automatically.</p>
                    <a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm mt-4">Open POS</a>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <div class="card card-pad">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h2 class="section-title">Sales by category</h2>
                    <p class="mt-0.5 text-xs text-muted">Category sales use saved sale prices.</p>
                </div>
                <span class="badge badge-neutral">{{ $categories->count() }} categories</span>
            </div>

            <div class="mt-5">
                @if ($categories->isEmpty())
                    <p class="rounded-lg border border-dashed border-line bg-slate-50 p-6 text-center text-sm text-muted">No category sales in this period.</p>
                @else
                    <x-bar-list :items="$categories" label-key="name" value-key="total" color-key="color" />
                @endif
            </div>
        </div>

        <div class="card card-pad">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h2 class="section-title">Payment methods</h2>
                    <p class="mt-0.5 text-xs text-muted">How customers paid in this period.</p>
                </div>
                <span class="badge badge-neutral">{{ $payments->count() }} methods</span>
            </div>

            <div class="mt-5">
                @if ($payments->isEmpty())
                    <p class="rounded-lg border border-dashed border-line bg-slate-50 p-6 text-center text-sm text-muted">No payments recorded in this period.</p>
                @else
                    <x-bar-list :items="$paymentRows" label-key="name" value-key="total" />
                    <div class="mt-4 space-y-1.5 border-t border-line pt-3">
                        @foreach ($payments as $payment)
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <span class="text-muted">{{ payment_method_label($payment->payment_method) }}</span>
                                <span class="num text-ink">{{ $payment->transactions }} {{ \Illuminate\Support\Str::plural('sale', $payment->transactions) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between gap-2 border-b border-line px-5 py-4">
                <div>
                    <h2 class="section-title">Top products</h2>
                    <p class="mt-0.5 text-xs text-muted">Ranked by quantity sold.</p>
                </div>
                <span class="badge badge-orange">Top {{ $products->count() }}</span>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th class="text-right">Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>
                                    <p class="font-medium text-ink">{{ $product->product_name }}</p>
                                    <p class="mt-0.5 text-[11px] text-muted">{{ $product->unit }}</p>
                                </td>
                                <td class="num text-muted">{{ quantity($product->quantity) }}</td>
                                <td class="num text-right font-semibold text-ink">{{ peso($product->total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-sm text-muted">No products sold in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between gap-2 border-b border-line px-5 py-4">
                <div>
                    <h2 class="section-title">Cashier performance</h2>
                    <p class="mt-0.5 text-xs text-muted">Completed sales by staff member.</p>
                </div>
                <span class="badge badge-neutral">{{ $cashiers->count() }} staff</span>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cashier</th>
                            <th>Sales</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cashiers as $cashier)
                            <tr>
                                <td class="font-medium text-ink">{{ $cashier->name }}</td>
                                <td class="num text-muted">{{ number_format($cashier->transactions) }}</td>
                                <td class="num text-right font-semibold text-ink">{{ peso($cashier->total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-sm text-muted">No cashier sales in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4 overflow-hidden">
        <div class="flex items-center justify-between gap-2 border-b border-line px-5 py-4">
            <div>
                <h2 class="section-title">Daily totals</h2>
                <p class="mt-0.5 text-xs text-muted">Every day in the selected range, including days with no sales.</p>
            </div>
            <span class="badge badge-neutral">{{ count($daily['keys']) }} {{ \Illuminate\Support\Str::plural('day', count($daily['keys'])) }}</span>
        </div>

        <div class="max-h-80 overflow-y-auto scrollbar-thin">
            <div class="table-wrap">
                <table class="table">
                    <thead class="sticky top-0 z-10 bg-white">
                        <tr>
                            <th>Date</th>
                            <th>Transactions</th>
                            <th class="text-right">Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daily['keys'] as $index => $key)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <span class="text-sm text-ink">{{ \Illuminate\Support\Carbon::parse($key)->format('D, M j, Y') }}</span>
                                </td>
                                <td class="num text-muted">{{ number_format($daily['transactions'][$index]) }}</td>
                                <td class="num text-right font-semibold {{ $daily['values'][$index] > 0 ? 'text-ink' : 'text-muted' }}">{{ peso($daily['values'][$index]) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
