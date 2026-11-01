@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $hour = (int) now()->format('H');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $firstName = str($user->name)->explode(' ')->first();
        $hasPeriodSales = array_sum($series['values']) > 0;
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="page-title">{{ $greeting }}, {{ $firstName }}</h1>
            <p class="page-subtitle">Here is how {{ setting('business_name', 'the karinderya') }} is doing today.</p>
        </div>

        <span class="inline-flex items-center gap-2 self-start rounded-lg border border-line px-3 py-2 text-xs text-muted lg:self-auto">
            <x-icon name="calendar" class="h-4 w-4" /> {{ now()->format('F j, Y') }}
        </span>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Today's gross sales" :value="peso($today['gross'])" icon="circle-dollar-sign" :delta="$grossDelta" />
        <x-stat-card label="Today's net sales" :value="peso($today['net'])" icon="trending-up" :delta="$netDelta" hint="Gross sales minus tax" />
        <x-stat-card label="Transactions" :value="number_format($today['transactions'])" icon="receipt-text" :delta="$transactionsDelta" :hint="$today['transactions'].' transactions · '.quantity($today['items']).' items sold'" />
        <x-stat-card label="Average sale" :value="peso($today['average'])" icon="calculator" :delta="$averageDelta" />
    </div>

    <div class="inventory-summary mt-4 grid gap-4 sm:grid-cols-2">
        <a href="{{ \Illuminate\Support\Facades\Route::has('products.index') ? route('products.index') : '#' }}" class="group flex items-center justify-between gap-4 rounded-lg border border-line px-4 py-3 transition hover:bg-slate-50">
            <div class="flex items-center gap-3">
                <span class="text-muted">
                    <x-icon name="package" class="h-4 w-4" />
                </span>
                <div class="flex items-baseline gap-2">
                    <p class="text-sm font-medium text-muted">Products</p>
                    <p class="num text-sm font-semibold text-ink">{{ number_format($productCount) }}</p>
                </div>
            </div>
            <span class="flex items-center gap-1 text-xs text-muted">Manage <x-icon name="arrow-up-right" class="h-3.5 w-3.5" /></span>
        </a>

        <a href="{{ \Illuminate\Support\Facades\Route::has('inventory.index') ? route('inventory.index', ['low_stock' => 1]) : '#' }}" class="group flex items-center justify-between gap-4 rounded-lg border border-line px-4 py-3 transition hover:bg-slate-50">
            <div class="flex items-center gap-3">
                <span class="{{ $lowStockCount > 0 ? 'text-amber-700' : 'text-success' }}">
                    <x-icon :name="$lowStockCount > 0 ? 'alert-triangle' : 'package-check'" class="h-4 w-4" />
                </span>
                <div class="flex flex-wrap items-baseline gap-x-2">
                    <p class="text-sm font-medium text-muted">Low-stock products</p>
                    <p class="num text-sm font-semibold text-ink">{{ number_format($lowStockCount) }}</p>
                </div>
            </div>
            <span class="shrink-0 text-xs font-medium {{ $lowStockCount > 0 ? 'text-amber-700' : 'text-success' }}">{{ $lowStockCount > 0 ? 'Review stock' : 'All stocked' }}</span>
        </a>
    </div>

    <div class="mt-6 space-y-4">
        <div class="card card-pad sales-trend">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 class="section-title">Sales trend</h2>
                    <p class="mt-0.5 text-xs text-muted">{{ $periodLabel }}</p>
                </div>
                <div class="period-switch inline-flex rounded-lg border border-line p-1" role="group" aria-label="Sales trend period">
                    @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $value => $label)
                        <a href="{{ route('dashboard', ['period' => $value]) }}" class="rounded-md px-3 py-1.5 text-xs font-medium {{ $period === $value ? 'bg-white text-ink shadow-sm' : 'text-muted hover:text-ink' }}" @if ($period === $value) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            @if ($hasPeriodSales)
                <div class="hidden sm:block"><x-line-chart :labels="$series['labels']" :values="$series['values']" :height="220" /></div>
                <div class="sm:hidden"><x-line-chart :labels="$series['labels']" :values="$series['values']" :height="240" :width="420" :max-labels="4" /></div>
            @else
                <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-line bg-slate-50/50 px-6 py-12 text-center">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-orange-soft text-orange-dark">
                        <x-icon name="chart-column" class="h-5 w-5" />
                    </div>
                    <h3 class="mt-3 font-display text-base font-semibold">No sales yet</h3>
                    <p class="mt-1 max-w-xs text-sm text-muted">Sales recorded at the counter will show up here automatically.</p>
                    <a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm mt-4">Start a sale</a>
                </div>
            @endif
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
        <div class="card card-pad">
            <div class="mb-4 flex items-start justify-between gap-2">
                <div>
                    <h2 class="section-title">Best sellers</h2>
                    <p class="mt-0.5 text-xs text-muted">Last 7 days by quantity</p>
                </div>
                <x-icon name="chart-column" class="h-4 w-4 text-muted" />
            </div>

            @if ($bestSellers->isEmpty())
                <div class="rounded-lg border border-dashed border-line px-4 py-10 text-center">
                    <p class="text-sm font-medium text-ink">No products sold yet</p>
                    <p class="mt-1 text-xs text-muted">Your best sellers will appear after the first sale.</p>
                </div>
            @else
                <ol class="space-y-3">
                    @foreach ($bestSellers as $index => $product)
                        <li class="flex items-center gap-3">
                            <span class="num flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-medium text-muted">{{ $index + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">{{ $product->product_name }}</p>
                                <p class="text-xs text-muted">{{ quantity($product->quantity) }} {{ $product->unit }}{{ (float) $product->quantity == 1 ? '' : 's' }} sold</p>
                            </div>
                            <span class="num shrink-0 text-sm font-semibold text-ink">{{ peso($product->total) }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
        <div class="card card-pad">
            <div class="mb-5">
                <h2 class="section-title">Sales by category</h2>
                <p class="mt-0.5 text-xs text-muted">{{ $periodLabel }}</p>
            </div>

            @if ($categories->isEmpty())
                <div class="rounded-lg border border-dashed border-line px-4 py-10 text-center">
                    <p class="text-sm font-medium text-ink">Nothing to break down yet</p>
                    <p class="mt-1 text-xs text-muted">Category totals appear after your first completed sale.</p>
                </div>
            @else
                <x-bar-list :items="$categories" />
            @endif
        </div>
        </div>

        <div class="card card-pad">
            <div class="mb-5 flex items-center justify-between gap-2">
                <div>
                    <h2 class="section-title">Top products</h2>
                    <p class="mt-0.5 text-xs text-muted">{{ $periodLabel }}</p>
                </div>
                @if (\Illuminate\Support\Facades\Route::has('reports.index'))
                    <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-orange-dark hover:underline">Full reports</a>
                @endif
            </div>

            @if ($topProducts->isEmpty())
                <div class="rounded-lg border border-dashed border-line px-4 py-10 text-center">
                    <p class="text-sm font-medium text-ink">No product sales in this period</p>
                    <p class="mt-1 text-xs text-muted">Try a different period or record a new sale.</p>
                </div>
            @else
                <div class="table-wrap -mx-1">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topProducts as $product)
                                <tr>
                                    <td class="font-medium">{{ $product->product_name }}</td>
                                    <td class="num text-right text-muted">{{ quantity($product->quantity) }}</td>
                                    <td class="num text-right font-semibold">{{ peso($product->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
