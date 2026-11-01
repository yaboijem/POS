@extends('layouts.app')

@section('title', 'Products')
@section('eyebrow', 'Catalogue')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Products</h1>
            <p class="page-subtitle">{{ number_format($products->total()) }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }} in your catalogue. Prices update on the POS immediately.</p>
        </div>
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Add product
        </a>
    </div>

    <form method="GET" action="{{ route('products.index') }}" class="card mb-4 p-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <div class="relative xl:col-span-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input type="search" name="search" value="{{ $filters['search'] }}" class="input pl-9" placeholder="Search name or product code">
            </div>

            <select name="category" class="input" aria-label="Filter by category">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $filters['category'] === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>

            <select name="status" class="input" aria-label="Filter by availability">
                <option value="">Any availability</option>
                <option value="available" @selected($filters['status'] === 'available')>Available</option>
                <option value="unavailable" @selected($filters['status'] === 'unavailable')>Not available</option>
            </select>

            <select name="stock" class="input" aria-label="Filter by stock">
                <option value="">Any stock level</option>
                <option value="low" @selected($filters['stock'] === 'low')>Low stock</option>
                <option value="out" @selected($filters['stock'] === 'out')>Out of stock</option>
            </select>

            <select name="sort" class="input" aria-label="Sort products">
                <option value="recent" @selected($filters['sort'] === 'recent')>Recently updated</option>
                <option value="name_asc" @selected($filters['sort'] === 'name_asc')>Name A–Z</option>
                <option value="name_desc" @selected($filters['sort'] === 'name_desc')>Name Z–A</option>
                <option value="price_asc" @selected($filters['sort'] === 'price_asc')>Price low to high</option>
                <option value="price_desc" @selected($filters['sort'] === 'price_desc')>Price high to low</option>
                <option value="stock_asc" @selected($filters['sort'] === 'stock_asc')>Stock low to high</option>
                <option value="stock_desc" @selected($filters['sort'] === 'stock_desc')>Stock high to low</option>
            </select>
        </div>

        <div class="mt-3 flex items-center justify-end gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            <button type="submit" class="btn btn-secondary btn-sm">
                <x-icon name="list-filter" class="h-3.5 w-3.5" />
                Apply filters
            </button>
        </div>
    </form>

    @if ($products->isEmpty())
        <x-empty-state
            icon="package"
            title="{{ $filters['search'] || $filters['category'] || $filters['status'] || $filters['stock'] ? 'No products match those filters' : 'No products yet' }}"
            message="{{ $filters['search'] || $filters['category'] || $filters['status'] || $filters['stock'] ? 'Try clearing a filter or searching for a different name.' : 'Add your rice meals, viands, drinks, and snacks to start selling.' }}"
            :action="route('products.create')"
            action-label="Add product"
        />
    @else
        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if ($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="" class="h-10 w-10 shrink-0 rounded-lg border border-line object-cover">
                                        @else
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-soft font-display text-sm font-bold text-orange-dark">{{ strtoupper(substr($product->name, 0, 1)) }}</span>
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('products.edit', $product) }}" class="block max-w-56 truncate font-medium text-ink hover:text-orange-dark">{{ $product->name }}</a>
                                            <p class="num text-xs text-muted">{{ $product->sku }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1.5 text-xs text-muted">
                                        <span class="h-2 w-2 rounded-full" style="background-color: {{ $product->category?->color ?? '#94A3B8' }}"></span>
                                        {{ $product->category?->name ?? 'Uncategorised' }}
                                    </span>
                                </td>
                                <td>
                                    <div x-data="{ open: false, price: '{{ $product->selling_price }}', saving: false }">
                                        <div class="flex items-center gap-1.5">
                                            <span class="num whitespace-nowrap font-semibold">{{ peso($product->selling_price) }}</span>
                                            <button type="button" class="rounded p-1 text-slate-400 transition hover:bg-orange-soft hover:text-orange-dark" @click="open = true" title="Edit price">
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                        @if ($product->cost_price !== null)
                                            @php($margin = (float) $product->selling_price > 0 ? (((float) $product->selling_price - (float) $product->cost_price) / (float) $product->selling_price) * 100 : 0)
                                            <p class="num mt-0.5 text-[11px] text-muted">Cost {{ peso($product->cost_price) }} · {{ number_format($margin, 0) }}% margin</p>
                                        @endif

                                        <div x-cloak x-show="open" class="modal-backdrop" @keydown.escape.window="open = false">
                                            <div class="modal-panel max-w-md p-6" role="dialog" aria-modal="true" aria-labelledby="price-title-{{ $product->id }}">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div>
                                                        <h3 id="price-title-{{ $product->id }}" class="font-display text-lg font-semibold">Update selling price</h3>
                                                        <p class="mt-1 text-sm text-muted">{{ $product->name }}</p>
                                                    </div>
                                                    <button type="button" class="btn btn-ghost btn-sm" @click="open = false" aria-label="Close">
                                                        <x-icon name="x" class="h-4 w-4" />
                                                    </button>
                                                </div>

                                                <div class="mt-5 rounded-lg bg-slate-50 px-4 py-3 text-sm">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-muted">Current price</span>
                                                        <span class="num font-semibold">{{ peso($product->selling_price) }}</span>
                                                    </div>
                                                    <div class="mt-1.5 flex items-center justify-between border-t border-line pt-1.5">
                                                        <span class="text-muted">New price</span>
                                                        <span class="num font-semibold text-orange-dark" x-text="new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(price || 0)">₱0.00</span>
                                                    </div>
                                                </div>

                                                <form method="POST" action="{{ route('products.price', $product) }}" class="mt-4 space-y-4" @submit="saving = true">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div>
                                                        <label for="price-{{ $product->id }}" class="label">New selling price</label>
                                                        <div class="relative">
                                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-muted">₱</span>
                                                            <input id="price-{{ $product->id }}" name="selling_price" type="number" min="0" step="0.01" x-model="price" class="input num pl-8" required>
                                                        </div>
                                                        <p class="hint">The POS uses the new price on the next sale. Completed sales keep their original price.</p>
                                                    </div>
                                                    <div>
                                                        <label for="note-{{ $product->id }}" class="label">Reason <span class="font-normal text-muted">(optional)</span></label>
                                                        <input id="note-{{ $product->id }}" name="note" type="text" maxlength="200" class="input" placeholder="e.g. Ingredient price increase">
                                                    </div>
                                                    <div class="flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row sm:justify-end">
                                                        <button type="button" class="btn btn-secondary" @click="open = false">Cancel</button>
                                                        <button type="submit" class="btn btn-primary" :disabled="saving">
                                                            <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                                            Save new price
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if (! $product->track_inventory)
                                        <span class="badge badge-neutral">Not tracked</span>
                                    @elseif ((float) $product->stock_quantity <= 0)
                                        <span class="badge badge-danger">Out of stock</span>
                                    @elseif ($product->isLowStock())
                                        <span class="badge badge-warning">{{ quantity($product->stock_quantity) }} left</span>
                                    @else
                                        <span class="num text-sm text-ink">{{ quantity($product->stock_quantity) }}</span>
                                    @endif
                                </td>
                                <td class="text-sm capitalize text-muted">{{ $product->unit }}</td>
                                <td>
                                    @if (! $product->is_available)
                                        <span class="badge badge-neutral">Hidden</span>
                                    @elseif ($product->track_inventory && (float) $product->stock_quantity <= 0)
                                        <span class="badge badge-danger">Out of stock</span>
                                    @else
                                        <span class="badge badge-success">Available</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-xs text-muted">{{ $product->updated_at->diffForHumans() }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <form method="POST" action="{{ route('products.availability', $product) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="btn btn-ghost btn-sm"
                                                title="{{ $product->is_available ? 'Hide from POS' : 'Show on POS' }}"
                                                @if ($product->is_available)
                                                    onclick="event.preventDefault(); window.confirmAction({ title: 'Hide this product?', message: @js('“'.$product->name.'” will not be purchasable until you make it available again.'), confirmLabel: 'Hide product', onConfirm: () => this.closest('form').requestSubmit() })"
                                                @endif
                                            >
                                                <x-icon :name="$product->is_available ? 'ban' : 'circle-check'" class="h-4 w-4" />
                                            </button>
                                        </form>
                                        <a href="{{ route('products.edit', $product) }}" class="btn btn-ghost btn-sm" title="Edit product">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>
                                        <button
                                            type="button"
                                            class="btn btn-ghost btn-sm text-danger hover:bg-red-50 hover:text-danger"
                                            title="Archive product"
                                            onclick="window.confirmAction({ title: 'Archive product?', message: @js('“'.$product->name.'” will be removed from the menu. Past receipts and reports stay intact.'), confirmLabel: 'Archive product', onConfirm: () => document.getElementById('archive-product-{{ $product->id }}').requestSubmit() })"
                                        >
                                            <x-icon name="trash-2" class="h-4 w-4" />
                                        </button>
                                        <form id="archive-product-{{ $product->id }}" method="POST" action="{{ route('products.destroy', $product) }}" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-line px-4 py-3">
                {{ $products->links() }}
            </div>
        </div>
    @endif
@endsection
