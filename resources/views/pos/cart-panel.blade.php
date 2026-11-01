@php($mobile = $mobile ?? false)

<div class="flex h-full min-h-0 flex-col bg-white">
    <div class="flex shrink-0 items-start justify-between gap-2 border-b border-line px-4 py-3.5">
        <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-orange-dark">Order</p>
            <h2 class="font-display text-base font-bold text-ink">Current order</h2>
            <p class="mt-0.5 text-xs text-muted" x-text="cart.length ? cart.length + (cart.length === 1 ? ' item' : ' items') : 'No items yet'"></p>
        </div>
        <div class="flex shrink-0 items-center gap-1">
            <button
                type="button"
                x-show="cart.length > 0"
                x-cloak
                @click="clearCart()"
                class="btn btn-ghost btn-sm text-danger hover:bg-red-50 hover:text-danger"
            >
                <x-icon name="trash-2" class="h-3.5 w-3.5" /> Clear
            </button>
            @if ($mobile)
                <button type="button" class="btn btn-ghost btn-sm" @click="mobileCartOpen = false" aria-label="Close cart">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            @endif
        </div>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto p-3 scrollbar-thin">
        <div x-show="cart.length === 0" class="flex h-full min-h-48 flex-col items-center justify-center px-4 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-orange-soft text-orange-dark">
                <x-icon name="shopping-bag" class="h-7 w-7" />
            </span>
            <p class="mt-3 font-display text-sm font-bold text-ink">Your cart is empty</p>
            <p class="mt-1 max-w-52 text-xs leading-relaxed text-muted">Tap a product on the left to start an order.</p>
        </div>

        <div class="space-y-2">
            <template x-for="item in cart" :key="item.id">
                <div class="rounded-xl border border-line bg-white p-3 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold leading-snug text-ink" x-text="item.name"></p>
                            <p class="mt-0.5 text-[11px] text-muted">
                                <span class="num" x-text="money(item.price_cents)"></span>
                                <span> / </span>
                                <span x-text="item.unit"></span>
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="removeItem(item)"
                            class="-mr-1 -mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-muted transition hover:bg-red-50 hover:text-danger"
                            :aria-label="'Remove ' + item.name"
                        >
                            <x-icon name="trash-2" class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="mt-2.5 flex items-center justify-between gap-2">
                        <div class="inline-flex items-center rounded-lg border border-line bg-white">
                            <button
                                type="button"
                                @click="decreaseItem(item)"
                                class="flex h-9 w-9 items-center justify-center rounded-l-lg text-muted transition hover:bg-slate-50 hover:text-ink"
                                :aria-label="'Decrease ' + item.name"
                            >
                                <x-icon name="minus" class="h-3.5 w-3.5" />
                            </button>
                            <input
                                type="number"
                                inputmode="decimal"
                                :value="item.qty"
                                :min="item.whole_quantity ? 1 : 0.001"
                                :step="item.whole_quantity ? 1 : 0.25"
                                @change="setQuantity(item, $event.target.value)"
                                class="num h-9 w-14 border-x border-line bg-white text-center text-sm font-semibold text-ink focus:border-orange focus:outline-none"
                                :aria-label="'Quantity of ' + item.name"
                            >
                            <button
                                type="button"
                                @click="increaseItem(item)"
                                class="flex h-9 w-9 items-center justify-center rounded-r-lg text-muted transition hover:bg-slate-50 hover:text-ink"
                                :aria-label="'Increase ' + item.name"
                            >
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <p class="num text-sm font-bold text-ink" x-text="money(lineTotal(item))"></p>
                    </div>

                    <p
                        x-show="item.track_inventory && item.stock_remaining !== null && item.qty >= item.stock_remaining"
                        x-cloak
                        class="mt-1.5 text-[10px] font-semibold text-danger"
                    >
                        Stock limit reached (<span x-text="item.formatted_stock + ' ' + item.unit"></span>).
                    </p>
                </div>
            </template>
        </div>
    </div>

    <div class="shrink-0 border-t border-line bg-white p-4 safe-bottom">
        <div class="space-y-1.5 text-sm">
            <div class="flex items-center justify-between gap-2">
                <span class="text-muted">Subtotal</span>
                <span class="num font-semibold text-ink" x-text="money(subtotalCents)"></span>
            </div>

            <div x-show="discountCents > 0" x-cloak class="flex items-center justify-between gap-2">
                <span class="text-muted">Discount</span>
                <span class="num font-semibold text-emerald-700">−<span x-text="money(discountCents)"></span></span>
            </div>

            <div x-show="taxCents > 0" x-cloak class="flex items-center justify-between gap-2">
                <span class="text-muted">
                    Tax <span x-show="config.taxInclusive" class="text-[11px]">(included)</span>
                </span>
                <span class="num font-semibold text-ink" x-text="money(taxCents)"></span>
            </div>
        </div>

        @if (auth()->user()->isAdmin())
            <div class="mt-3 border-t border-dashed border-line pt-3" x-cloak>
                <div x-show="!discountType">
                    <button type="button" :disabled="!!pendingCheckout" class="inline-flex items-center gap-1.5 text-xs font-semibold text-orange-dark hover:text-orange" @click="discountType = 'fixed'; discountValue = ''">
                        <x-icon name="tag" class="h-3.5 w-3.5" /> Add admin discount
                    </button>
                </div>

                <div x-show="discountType" class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-ink">Admin discount</p>
                        <button type="button" :disabled="!!pendingCheckout" class="text-[11px] font-semibold text-muted hover:text-danger" @click="discountType = ''; discountValue = ''">
                            Remove
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <select x-model="discountType" :disabled="!!pendingCheckout" class="input min-w-0 flex-1 py-2 text-xs" aria-label="Discount type">
                            <option value="fixed">Amount (₱)</option>
                            <option value="percent">Percent (%)</option>
                        </select>
                        <input
                            x-model="discountValue"
                            :disabled="!!pendingCheckout"
                            type="number"
                            inputmode="decimal"
                            min="0"
                            :max="discountType === 'percent' ? 100 : null"
                            step="0.01"
                            placeholder="0"
                            class="input w-24 py-2 text-right text-xs num"
                            aria-label="Discount value"
                        >
                    </div>
                    <p class="text-[10px] leading-relaxed text-muted">Only administrators can discount. The server rechecks this when the sale is saved.</p>
                </div>
            </div>
        @endif

        <div class="mt-3 flex items-end justify-between gap-3 border-t border-line pt-3">
            <span class="text-sm font-semibold text-ink">Total</span>
            <span class="num text-2xl font-bold leading-none text-ink" x-text="money(totalCents)"></span>
        </div>

        <button
            type="button"
            class="btn btn-primary btn-lg mt-3 w-full"
            :disabled="cart.length === 0"
            @click="mobileCartOpen = false; openCheckout()"
        >
            Checkout
            <x-icon name="chevron-right" class="h-4 w-4" />
        </button>
        <p class="mt-2 text-center text-[10px] leading-relaxed text-muted">Prices and stock are checked again when the sale is saved.</p>
    </div>
</div>
