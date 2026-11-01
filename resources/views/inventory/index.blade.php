@extends('layouts.app')

@section('title', 'Inventory')
@section('eyebrow', 'Stock')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Inventory</h1>
            <p class="page-subtitle">Stock levels for products you count. Meals without tracking are not listed here.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('inventory.movements') }}" class="btn btn-secondary">
                <x-icon name="history" class="h-4 w-4" />
                Movement history
            </a>
            <a href="{{ route('products.index') }}" class="btn btn-primary">
                <x-icon name="package" class="h-4 w-4" />
                Manage products
            </a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Tracked products" :value="number_format($trackedCount)" icon="boxes" hint="Products with stock counting turned on" />
        <x-stat-card label="Low stock" :value="number_format($lowCount)" icon="alert-triangle" tone="warning" hint="At or below the alert level" />
        <x-stat-card label="Out of stock" :value="number_format($outCount)" icon="package-x" :tone="$outCount > 0 ? 'danger' : 'success'" hint="Needs restocking" />
        <x-stat-card label="Stock value at cost" :value="peso($stockValue)" icon="calculator" hint="Quantity × recorded cost price" />
    </div>

    <form method="GET" action="{{ route('inventory.index') }}" class="card mt-5 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input type="search" name="search" value="{{ $search }}" class="input pl-9" placeholder="Search product name or code">
            </div>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-muted">
                <input type="checkbox" name="low_stock" value="1" class="h-4 w-4 rounded border-line text-orange focus:ring-orange/30" @checked($lowOnly)>
                Low stock only
            </label>
            <div class="flex gap-2">
                <a href="{{ route('inventory.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                <button type="submit" class="btn btn-secondary btn-sm">
                    <x-icon name="list-filter" class="h-3.5 w-3.5" /> Apply
                </button>
            </div>
        </div>
    </form>

    @if ($products->isEmpty())
        <div class="mt-5">
            <x-empty-state
                icon="boxes"
                title="{{ $search || $lowOnly ? 'No tracked products match' : 'No tracked products yet' }}"
                message="{{ $search || $lowOnly ? 'Try clearing the search or low-stock filter.' : 'Turn on stock tracking when adding or editing a product to see it here.' }}"
                :action="route('products.index')"
                action-label="Manage products"
            />
        </div>
    @else
        <div class="card mt-5 overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>On hand</th>
                            <th>Alert at</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            @php($threshold = (float) ($product->low_stock_threshold ?? $defaultThreshold))
                            @php($isOut = (float) $product->stock_quantity <= 0)
                            @php($isLow = ! $isOut && (float) $product->stock_quantity <= $threshold)
                            <tr class="{{ $isOut ? 'bg-red-50/40' : ($isLow ? 'bg-amber-50/30' : '') }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if ($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="" class="h-9 w-9 shrink-0 rounded-lg border border-line object-cover">
                                        @else
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orange-soft text-xs font-bold text-orange-dark">{{ strtoupper(substr($product->name, 0, 1)) }}</span>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="max-w-56 truncate font-medium">{{ $product->name }}</p>
                                            <p class="num text-xs text-muted">{{ $product->sku }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-xs text-muted">{{ $product->category?->name ?? '—' }}</td>
                                <td>
                                    <span class="num text-base font-bold {{ $isOut ? 'text-danger' : ($isLow ? 'text-amber-700' : 'text-ink') }}">{{ quantity($product->stock_quantity) }}</span>
                                    <span class="text-xs text-muted">{{ $product->unit }}</span>
                                </td>
                                <td class="num text-sm text-muted">{{ quantity($threshold) }}</td>
                                <td>
                                    @if ($isOut)
                                        <span class="badge badge-danger">Out of stock</span>
                                    @elseif ($isLow)
                                        <span class="badge badge-warning">Low stock</span>
                                    @else
                                        <span class="badge badge-success">In stock</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-xs text-muted">{{ $product->updated_at->diffForHumans() }}</td>
                                <td>
                                    <div class="flex justify-end" x-data="{ open: false, type: 'stock_in', quantity: '', saving: false, get label() { return this.type === 'adjustment' ? 'New stock count' : (this.type === 'stock_in' ? 'Quantity received' : 'Quantity removed'); } }">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="open = true">
                                            <x-icon name="sliders-horizontal" class="h-3.5 w-3.5" />
                                            Adjust
                                        </button>

                                        <div x-cloak x-show="open" class="modal-backdrop" @keydown.escape.window="open = false">
                                            <div class="modal-panel max-w-md p-6" role="dialog" aria-modal="true" aria-labelledby="stock-title-{{ $product->id }}">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div>
                                                        <h3 id="stock-title-{{ $product->id }}" class="font-display text-lg font-semibold">Adjust stock</h3>
                                                        <p class="mt-1 text-sm text-muted">{{ $product->name }}</p>
                                                    </div>
                                                    <button type="button" class="btn btn-ghost btn-sm" @click="open = false" aria-label="Close">
                                                        <x-icon name="x" class="h-4 w-4" />
                                                    </button>
                                                </div>

                                                <div class="mt-4 flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
                                                    <span class="text-sm text-muted">Current stock</span>
                                                    <span class="num text-lg font-bold">{{ quantity($product->stock_quantity) }} <span class="text-xs font-normal text-muted">{{ $product->unit }}</span></span>
                                                </div>

                                                <form method="POST" action="{{ route('inventory.adjust', $product) }}" class="mt-4 space-y-4" @submit="saving = true">
                                                    @csrf
                                                    <div>
                                                        <label for="type-{{ $product->id }}" class="label">What happened?</label>
                                                        <select id="type-{{ $product->id }}" name="type" x-model="type" class="input">
                                                            <option value="stock_in">Stock in — received or restocked</option>
                                                            <option value="stock_out">Stock out — used, damaged, or removed</option>
                                                            <option value="adjustment">Set exact count — after a physical count</option>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label for="quantity-{{ $product->id }}" class="label" x-text="label">Quantity</label>
                                                        <div class="flex items-center gap-2">
                                                            <input id="quantity-{{ $product->id }}" name="quantity" type="number" min="0" step="0.001" x-model="quantity" class="input num" placeholder="0" required>
                                                            <span class="text-sm text-muted">{{ $product->unit }}</span>
                                                        </div>
                                                        <p class="hint" x-show="type === 'stock_in'">Added to the current count.</p>
                                                        <p class="hint" x-show="type === 'stock_out'" x-cloak>Subtracted from the current count. Cannot go below zero.</p>
                                                        <p class="hint" x-show="type === 'adjustment'" x-cloak>Replaces the current count with this exact number.</p>
                                                    </div>

                                                    <div>
                                                        <label for="reason-{{ $product->id }}" class="label">Reason <span class="font-normal text-muted" x-text="type === 'stock_in' ? '(optional)' : ''"></span></label>
                                                        <input id="reason-{{ $product->id }}" name="reason" type="text" maxlength="200" class="input" placeholder="e.g. Supplier delivery, physical count" :required="type !== 'stock_in'">
                                                    </div>

                                                    <div class="flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row sm:justify-end">
                                                        <button type="button" class="btn btn-secondary" @click="open = false">Cancel</button>
                                                        <button type="submit" class="btn btn-primary" :disabled="saving">
                                                            <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                                            Save stock change
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-line px-4 py-3">{{ $products->links() }}</div>
        </div>
    @endif
@endsection
