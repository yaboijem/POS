@extends('layouts.pos')

@section('title', 'Point of Sale')

@section('content')
    <div
        x-data="posTerminal({{ Js::from($config) }})"
        class="flex h-[calc(100dvh-4rem)] min-h-0 flex-col bg-page lg:flex-row"
    >
        {{-- Product feed --}}
        <section class="flex min-h-0 min-w-0 flex-1 flex-col">
            <div class="shrink-0 border-b border-line bg-white px-3 pb-3 pt-3 sm:px-5">
                <div class="flex items-center gap-2">
                    <div class="relative min-w-0 flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                        <input
                            x-ref="search"
                            x-model="search"
                            type="search"
                            autocomplete="off"
                            placeholder="Search dish, drink, or product code…"
                            aria-label="Search products"
                            class="input h-11 pl-9 pr-10"
                            @keydown.enter.prevent="addFirstProduct()"
                        >
                        <button
                            type="button"
                            x-show="search"
                            x-cloak
                            @click="search = ''; $refs.search.focus()"
                            class="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-muted hover:bg-slate-100 hover:text-ink"
                            aria-label="Clear search"
                        >
                            <x-icon name="x" class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="hidden shrink-0 items-center gap-1.5 rounded-lg border border-line bg-slate-50 px-2.5 py-2 text-[11px] font-medium text-muted sm:flex">
                        <kbd class="rounded border border-line bg-white px-1.5 py-0.5 font-sans text-[10px] font-semibold text-ink">/</kbd>
                        <span>to search</span>
                    </div>
                </div>

                <div class="-mx-1 mt-3 flex items-center gap-2 overflow-x-auto px-1 pb-0.5 scrollbar-thin">
                    <button
                        type="button"
                        @click="setCategory(null)"
                        :class="activeCategory === null ? 'border-orange bg-orange text-white shadow-sm' : 'border-line bg-white text-ink hover:border-orange/40 hover:bg-orange-soft/50'"
                        class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors"
                    >
                        <x-icon name="layout-grid" class="h-3.5 w-3.5" />
                        All
                        <span class="rounded-full bg-black/10 px-1.5 py-0.5 text-[10px] font-bold" x-text="config.totalProducts"></span>
                    </button>

                    <template x-for="category in categories" :key="category.id">
                        <button
                            type="button"
                            @click="setCategory(category.id)"
                            :class="activeCategory === category.id ? 'border-orange bg-orange text-white shadow-sm' : 'border-line bg-white text-ink hover:border-orange/40 hover:bg-orange-soft/50'"
                            class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-colors"
                        >
                            <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: activeCategory === category.id ? '#FFFFFF' : (category.color || '#F97316') }"></span>
                            <span x-text="category.name"></span>
                            <span
                                class="rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                                :class="activeCategory === category.id ? 'bg-black/10' : 'bg-slate-100 text-muted'"
                                x-text="category.count"
                            ></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-3 pb-24 scrollbar-thin sm:p-5 lg:pb-5">
                <div class="mb-3 flex items-end justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-orange-dark">Menu</p>
                        <p class="text-sm font-semibold text-ink">
                            <span x-text="loading ? 'Loading products…' : total + (total === 1 ? ' product' : ' products')"></span>
                            <span x-show="activeCategory !== null" class="font-normal text-muted"> in this category</span>
                        </p>
                    </div>
                    <button
                        type="button"
                        x-show="activeCategory !== null || search"
                        x-cloak
                        @click="search = ''; activeCategory = null"
                        class="text-xs font-semibold text-orange-dark hover:text-orange"
                    >Clear filters</button>
                </div>

                <template x-if="loading">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                        <template x-for="n in 8" :key="n">
                            <div class="overflow-hidden rounded-xl border border-line bg-white shadow-sm">
                                <div class="aspect-[4/3] animate-pulse bg-slate-100"></div>
                                <div class="space-y-2 p-3">
                                    <div class="h-3.5 w-4/5 animate-pulse rounded bg-slate-100"></div>
                                    <div class="h-3 w-1/2 animate-pulse rounded bg-slate-100"></div>
                                    <div class="mt-3 h-5 w-1/3 animate-pulse rounded bg-slate-100"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!loading && loadError">
                    <div class="card mx-auto max-w-md p-8 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-danger">
                            <x-icon name="alert-circle" class="h-6 w-6" />
                        </span>
                        <h2 class="mt-4 font-display text-base font-bold text-ink">Could not load the menu</h2>
                        <p class="mt-1 text-sm text-muted" x-text="loadError"></p>
                        <button type="button" class="btn btn-primary mt-5" @click="fetchProducts(true)">Try again</button>
                    </div>
                </template>

                <template x-if="!loading && !loadError && products.length === 0">
                    <div class="card mx-auto max-w-md p-8 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-orange-soft text-orange-dark">
                            <x-icon name="search" class="h-6 w-6" />
                        </span>
                        <h2 class="mt-4 font-display text-base font-bold text-ink">No products found</h2>
                        <p class="mt-1 text-sm text-muted" x-show="search">Try a different name or product code.</p>
                        <p class="mt-1 text-sm text-muted" x-show="!search">There are no available products in this category yet.</p>
                        <button type="button" class="btn btn-secondary mt-5" @click="search = ''; activeCategory = null">Show all products</button>
                    </div>
                </template>

                <template x-if="!loading && !loadError && products.length > 0">
                    <div>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                            <template x-for="product in products" :key="product.id">
                                <button
                                    type="button"
                                    @click="addProduct(product)"
                                    :disabled="product.track_inventory && product.stock_remaining <= 0"
                                    class="group relative flex min-h-[200px] flex-col overflow-hidden rounded-xl border border-line bg-white text-left shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-orange/40 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-orange/40 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0 disabled:hover:shadow-sm"
                                >
                                    <div class="relative aspect-[4/3] w-full overflow-hidden bg-orange-soft">
                                        <template x-if="product.image_url">
                                            <img :src="product.image_url" :alt="product.name" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                        </template>
                                        <template x-if="!product.image_url">
                                            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-orange-soft to-orange-50 text-orange/50">
                                                <x-icon name="utensils" class="h-9 w-9" />
                                            </div>
                                        </template>

                                        <span
                                            class="absolute left-2 top-2 max-w-[calc(100%-1rem)] truncate rounded-full px-2 py-1 text-[10px] font-bold backdrop-blur"
                                            :style="{ backgroundColor: (product.category_color || '#F97316') + '1A', color: product.category_color || '#EA580C' }"
                                            x-text="product.category"
                                        ></span>

                                        <template x-if="product.track_inventory && product.stock_remaining <= 0">
                                            <div class="absolute inset-0 flex items-center justify-center bg-slate-900/55">
                                                <span class="rounded-full bg-white px-3 py-1.5 text-[11px] font-bold text-danger">Out of stock</span>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="flex flex-1 flex-col p-3">
                                        <p class="line-clamp-2 text-sm font-semibold leading-snug text-ink" x-text="product.name"></p>
                                        <p
                                            class="mt-0.5 text-[11px]"
                                            :class="product.track_inventory && product.stock_remaining <= 5 ? 'font-semibold text-danger' : 'text-muted'"
                                            x-text="stockLabel(product)"
                                        ></p>

                                        <div class="mt-auto flex items-end justify-between gap-2 pt-3">
                                            <div class="min-w-0">
                                                <p class="num text-base font-bold leading-none text-ink" x-text="product.formatted_price"></p>
                                                <p class="mt-1 text-[10px] text-muted" x-text="'per ' + product.unit"></p>
                                            </div>
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange text-white shadow-sm transition group-hover:bg-orange-dark">
                                                <x-icon name="plus" class="h-4 w-4" />
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            </template>
                        </div>

                        <div class="mt-5 flex justify-center" x-show="nextPage">
                            <button type="button" class="btn btn-secondary" :disabled="loadingMore" @click="fetchProducts(false)">
                                <span x-show="!loadingMore">Load more products</span>
                                <span x-show="loadingMore" class="flex items-center gap-2">
                                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                                    Loading…
                                </span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        {{-- Desktop cart --}}
        <aside class="hidden w-[390px] shrink-0 border-l border-line bg-white lg:flex lg:min-h-0 lg:flex-col">
            @include('pos.cart-panel', ['mobile' => false])
        </aside>

        {{-- Mobile cart bar --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-white/95 p-3 shadow-[0_-6px_24px_rgba(15,23,42,0.08)] backdrop-blur lg:hidden safe-bottom">
            <button
                type="button"
                class="btn btn-primary min-h-12 w-full justify-between px-4"
                :disabled="cart.length === 0"
                @click="mobileCartOpen = true"
            >
                <span class="flex items-center gap-2">
                    <span class="relative flex h-8 w-8 items-center justify-center rounded-full bg-white/20">
                        <x-icon name="shopping-cart" class="h-4 w-4" />
                        <span
                            class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-white px-1 text-[10px] font-bold text-orange-dark"
                            x-text="cart.length"
                        ></span>
                    </span>
                    <span x-text="cart.length === 0 ? 'Your order is empty' : 'View order'"></span>
                </span>
                <span class="num" x-text="money(totalCents)"></span>
            </button>
        </div>

        {{-- Mobile cart drawer --}}
        <div x-cloak x-show="mobileCartOpen" class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="Current order">
            <div x-show="mobileCartOpen" x-transition.opacity class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="mobileCartOpen = false"></div>
            <div
                x-show="mobileCartOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl"
            >
                @include('pos.cart-panel', ['mobile' => true])
            </div>
        </div>

        {{-- Checkout modal --}}
        <div
            x-cloak
            x-show="checkoutOpen"
            class="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-labelledby="checkout-title"
            @keydown.escape.window="submitting || (checkoutOpen = false)"
        >
            <form class="modal-panel max-w-lg" @submit.prevent="submitCheckout()" @click.outside="submitting || (checkoutOpen = false)">
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
                    <div>
                        <h2 id="checkout-title" class="font-display text-lg font-bold text-ink">Complete sale</h2>
                        <p class="text-xs text-muted">Choose how the customer paid.</p>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm" :disabled="submitting" @click="checkoutOpen = false" aria-label="Close checkout">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <fieldset :disabled="submitting || !!pendingCheckout" class="space-y-4 p-5">
                    <div x-show="checkoutError" x-cloak class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-danger" x-text="checkoutError"></div>

                    <div class="rounded-xl border border-line bg-page p-4">
                        <div class="flex items-center justify-between gap-2 text-sm text-muted">
                            <span x-text="cart.length + (cart.length === 1 ? ' item' : ' items') + ' in this order'"></span>
                            <span class="num" x-text="money(subtotalCents)"></span>
                        </div>
                        <div class="mt-2 flex items-end justify-between gap-3 border-t border-line pt-2">
                            <span class="text-sm font-semibold text-ink">Total due</span>
                            <span class="num text-2xl font-bold text-ink" x-text="money(totalCents)"></span>
                        </div>
                    </div>

                    <div>
                        <p class="label">Payment method</p>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="method in config.paymentMethods" :key="method.value">
                                <button
                                    type="button"
                                    @click="selectPaymentMethod(method.value)"
                                    :class="paymentMethod === method.value ? 'border-orange bg-orange-soft text-orange-dark ring-1 ring-orange' : 'border-line bg-white text-ink hover:bg-slate-50'"
                                    class="flex min-h-12 items-center gap-2 rounded-xl border px-3 py-2.5 text-left text-sm font-semibold transition-colors"
                                >
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-current opacity-70"></span>
                                    <span x-text="method.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div x-show="paymentMethod === 'cash'" class="space-y-2">
                        <label for="amount-tendered" class="label">Cash received</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg font-bold text-muted">₱</span>
                            <input
                                id="amount-tendered"
                                x-model="amountTendered"
                                type="number"
                                inputmode="decimal"
                                min="0"
                                step="0.01"
                                class="input h-14 pl-9 text-2xl font-bold num"
                            >
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="btn btn-secondary btn-sm" @click="setExactAmount()">
                                Exact <span class="num">(<span x-text="money(totalCents)"></span>)</span>
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" @click="setCashAmount(100)">₱100</button>
                            <button type="button" class="btn btn-secondary btn-sm" @click="setCashAmount(200)">₱200</button>
                            <button type="button" class="btn btn-secondary btn-sm" @click="setCashAmount(500)">₱500</button>
                            <button type="button" class="btn btn-secondary btn-sm" @click="setCashAmount(1000)">₱1,000</button>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2.5">
                            <span class="text-sm font-semibold text-emerald-800">Change</span>
                            <span class="num text-lg font-bold text-emerald-800" x-text="money(changeCents)"></span>
                        </div>
                        <p x-show="shortCents > 0" x-cloak class="text-xs font-semibold text-danger">
                            Still short <span class="num" x-text="money(shortCents)"></span>.
                        </p>
                    </div>

                    <div x-show="paymentMethod !== 'cash'" x-cloak class="space-y-3">
                        <div x-show="paymentMethod === 'other'" x-cloak>
                            <label for="payment-label" class="label">Payment label</label>
                            <input id="payment-label" x-model="paymentLabel" type="text" maxlength="50" placeholder="e.g. Bank transfer, check" class="input">
                            <p class="mt-1 text-[11px] text-muted">Required so the sale record says how the customer paid.</p>
                        </div>

                        <div>
                            <label for="reference-number" class="label">Reference number <span class="font-normal text-muted">(optional)</span></label>
                            <input id="reference-number" x-model="referenceNumber" type="text" maxlength="100" placeholder="e.g. GCash reference number" class="input">
                        </div>

                        <div>
                            <label for="digital-amount" class="label">Amount received</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-base font-bold text-muted">₱</span>
                                <input id="digital-amount" x-model="amountTendered" type="number" inputmode="decimal" min="0" step="0.01" class="input h-12 pl-9 text-lg font-bold num">
                            </div>
                        </div>

                        <p class="flex items-start gap-2 rounded-lg bg-slate-50 px-3 py-2.5 text-[11px] leading-relaxed text-muted">
                            <x-icon name="alert-circle" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                            <span>Digital payments are recorded for tracking only. This app does not verify payments with GCash, Maya, or a bank.</span>
                        </p>
                    </div>

                    <div>
                        <label for="sale-notes" class="label">Note <span class="font-normal text-muted">(optional)</span></label>
                        <input id="sale-notes" x-model="notes" type="text" maxlength="500" placeholder="e.g. takeout, no onions" class="input">
                    </div>
                </fieldset>

                <div class="border-t border-line bg-page px-5 py-4">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <span class="text-sm font-semibold text-ink">Total</span>
                        <span class="num text-2xl font-bold text-ink" x-text="money(totalCents)"></span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="!canSubmit">
                        <span x-show="submitting" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <span x-show="!submitting" class="flex"><x-icon name="check-circle-2" class="h-5 w-5" /></span>
                        <span x-text="submitting ? 'Completing sale…' : (pendingCheckout ? 'Retry original checkout' : 'Complete sale')"></span>
                    </button>
                    <p class="mt-2 text-center text-[11px] text-muted">Prices and stock are checked again before this sale is saved.</p>
                </div>
            </form>
        </div>

        {{-- Receipt confirmation --}}
        <div
            x-cloak
            x-show="receiptOpen"
            class="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-labelledby="receipt-title"
            @keydown.escape.window="startNewOrder()"
        >
            <div class="modal-panel max-w-md" @click.outside="startNewOrder()">
                <div class="p-5 sm:p-6">
                    <div class="text-center">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                            <x-icon name="check-circle-2" class="h-8 w-8" />
                        </span>
                        <h2 id="receipt-title" class="mt-3 font-display text-xl font-bold text-ink">Sale complete</h2>
                        <p class="mt-1 text-sm text-muted">
                            <span class="font-semibold text-ink" x-text="completedSale?.transaction_number"></span>
                            <span class="mx-1">·</span>
                            <span class="num" x-text="completedSale ? money(Math.round(Number(completedSale.total) * 100)) : ''"></span>
                        </p>
                    </div>

                    <div class="mt-5 max-h-[46vh] overflow-y-auto rounded-xl border border-line bg-slate-50 p-3 scrollbar-thin">
                        <div class="mx-auto max-w-[80mm] bg-white p-4 shadow-sm" x-html="receiptHtml"></div>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <button type="button" class="btn btn-primary" @click="printReceipt()">
                            <x-icon name="printer" class="h-4 w-4" /> Print
                        </button>
                        <a :href="receiptPdfUrl" target="_blank" rel="noopener" class="btn btn-secondary">
                            <x-icon name="download" class="h-4 w-4" /> PDF
                        </a>
                        <a :href="receiptUrl" target="_blank" rel="noopener" class="btn btn-ghost">
                            <x-icon name="receipt-text" class="h-4 w-4" /> View receipt
                        </a>
                        <button type="button" class="btn btn-secondary" @click="startNewOrder()">
                            <x-icon name="plus" class="h-4 w-4" /> New order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
