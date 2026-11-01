import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

window.apiFetch = async (url, options = {}) => {
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
            ...(options.headers ?? {}),
        },
    });

    let payload = null;
    try {
        payload = await response.json();
    } catch {
        payload = null;
    }

    if (!response.ok) {
        const error = new Error(payload?.message ?? 'Something went wrong. Please try again.');
        error.status = response.status;
        error.errors = payload?.errors ?? {};
        throw error;
    }

    return payload;
};

window.copyText = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        window.toast('Copied to clipboard.');
    } catch {
        window.toast('Could not copy. Please select and copy manually.', 'error');
    }
};

document.addEventListener('alpine:init', () => {
    Alpine.store('toasts', {
        items: [],
        push(message, type = 'success') {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, type });
            setTimeout(() => this.dismiss(id), 4200);
        },
        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    });

    Alpine.store('confirm', {
        open: false,
        title: '',
        message: '',
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        tone: 'danger',
        onConfirm: null,
        ask(options = {}) {
            this.title = options.title ?? 'Are you sure?';
            this.message = options.message ?? '';
            this.confirmLabel = options.confirmLabel ?? 'Confirm';
            this.cancelLabel = options.cancelLabel ?? 'Cancel';
            this.tone = options.tone ?? 'danger';
            this.onConfirm = options.onConfirm ?? null;
            this.open = true;
        },
        cancel() {
            this.open = false;
            this.onConfirm = null;
        },
        confirm() {
            const callback = this.onConfirm;
            this.open = false;
            this.onConfirm = null;
            if (typeof callback === 'function') callback();
        },
    });

    Alpine.store('ui', {
        sidebarOpen: window.localStorage.getItem('kpos.sidebar') !== 'closed',
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            window.localStorage.setItem('kpos.sidebar', this.sidebarOpen ? 'open' : 'closed');
        },
    });

    Alpine.data('posTerminal', (config) => ({
        config,
        categories: config.categories ?? [],
        products: [],
        activeCategory: null,
        search: '',
        loading: true,
        loadingMore: false,
        loadError: '',
        page: 1,
        nextPage: null,
        total: config.totalProducts ?? 0,
        abortController: null,
        searchTimer: null,

        cart: [],
        mobileCartOpen: false,
        checkoutOpen: false,
        receiptOpen: false,
        submitting: false,
        checkoutError: '',
        idempotencyKey: null,
        pendingCheckout: null,

        paymentMethod: config.paymentMethods?.[0]?.value ?? 'cash',
        paymentLabel: '',
        amountTendered: '',
        referenceNumber: '',
        notes: '',
        discountType: '',
        discountValue: '',

        receiptHtml: '',
        receiptUrl: '',
        receiptPdfUrl: '',
        completedSale: null,

        init() {
            this.fetchProducts(true);
            this.$watch('search', () => {
                window.clearTimeout(this.searchTimer);
                this.searchTimer = window.setTimeout(() => this.fetchProducts(true), 280);
            });
            this.$watch('activeCategory', () => this.fetchProducts(true));
            this.$nextTick(() => this.$refs.search?.focus());
            this.keydownHandler = (event) => this.handleKeydown(event);
            window.addEventListener('keydown', this.keydownHandler);
        },

        destroy() {
            window.removeEventListener('keydown', this.keydownHandler);
            this.abortController?.abort();
        },

        handleKeydown(event) {
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target?.tagName) || event.target?.isContentEditable;

            if (event.key === '/' && !typing && !this.checkoutOpen && !this.receiptOpen && !this.mobileCartOpen) {
                event.preventDefault();
                this.$refs.search?.focus();
            }

            if (event.key === 'Escape') {
                if (this.receiptOpen) {
                    this.startNewOrder();
                } else if (this.checkoutOpen) {
                    this.checkoutOpen = false;
                } else if (this.mobileCartOpen) {
                    this.mobileCartOpen = false;
                }
            }
        },

        async fetchProducts(reset = true) {
            if (!reset && !this.nextPage) return;

            if (this.abortController) this.abortController.abort();
            const controller = new AbortController();
            this.abortController = controller;

            if (reset) {
                this.loading = true;
                this.loadError = '';
                this.page = 1;
            } else {
                this.loadingMore = true;
            }

            const params = new URLSearchParams({ page: String(reset ? 1 : this.nextPage) });
            if (this.search.trim()) params.set('search', this.search.trim());
            if (this.activeCategory) params.set('category', String(this.activeCategory));

            try {
                const data = await window.apiFetch(`${this.config.productsUrl}?${params.toString()}`, {
                    signal: controller.signal,
                });

                this.products = reset ? data.data : [...this.products, ...data.data];
                this.page = data.current_page;
                this.nextPage = data.next_page;
                this.total = data.total;
            } catch (error) {
                if (error.name === 'AbortError') return;
                this.loadError = error.message || 'Could not load products.';
            } finally {
                if (this.abortController === controller) {
                    this.loading = false;
                    this.loadingMore = false;
                }
            }
        },

        setCategory(categoryId) {
            this.activeCategory = categoryId;
        },

        addFirstProduct() {
            if (!this.loading && this.products.length > 0) this.addProduct(this.products[0]);
        },

        addProduct(product) {
            if (this.checkoutPending()) return;
            if (!product.is_available || (product.track_inventory && product.stock_remaining <= 0)) {
                window.toast('That product is out of stock.', 'error');
                return;
            }

            const existing = this.cart.find((item) => item.id === product.id);
            const step = product.whole_quantity ? 1 : 0.25;

            if (existing) {
                this.setQuantity(existing, existing.qty + step);
                return;
            }

            let quantity = product.whole_quantity ? 1 : Math.min(1, product.stock_remaining ?? 1);
            quantity = Math.max(product.whole_quantity ? 1 : 0.001, Math.round(quantity * 1000) / 1000);

            if (product.track_inventory && quantity > product.stock_remaining) {
                window.toast(`Only ${product.formatted_stock} ${product.unit} left.`, 'error');
                return;
            }

            this.cart.push({ ...product, qty: quantity });
            window.toast(`${product.name} added.`);
        },

        setQuantity(item, value) {
            if (this.checkoutPending()) return;
            let quantity = Number(value);
            const minimum = item.whole_quantity ? 1 : 0.001;

            if (!Number.isFinite(quantity) || quantity <= 0) quantity = minimum;
            quantity = Math.max(minimum, Math.round(quantity * 1000) / 1000);

            if (item.track_inventory && quantity > item.stock_remaining) {
                quantity = Math.max(minimum, item.stock_remaining);
                window.toast(`Only ${item.formatted_stock} ${item.unit} available.`, 'error');
            }

            if (item.whole_quantity) quantity = Math.floor(quantity);

            item.qty = quantity;
        },

        increaseItem(item) {
            this.setQuantity(item, item.qty + (item.whole_quantity ? 1 : 0.25));
        },

        decreaseItem(item) {
            const step = item.whole_quantity ? 1 : 0.25;
            const minimum = item.whole_quantity ? 1 : 0.001;
            const next = Math.round((item.qty - step) * 1000) / 1000;

            if (next < minimum) {
                this.removeItem(item);
                return;
            }

            this.setQuantity(item, next);
        },

        removeItem(item) {
            if (this.checkoutPending()) return;
            this.cart = this.cart.filter((entry) => entry.id !== item.id);
        },

        clearCart() {
            if (this.checkoutPending()) return;
            window.confirmAction({
                title: 'Clear this order?',
                message: 'All items will be removed from the cart. This cannot be undone.',
                confirmLabel: 'Clear order',
                onConfirm: () => {
                    if (this.checkoutPending()) return;
                    this.cart = [];
                    this.checkoutOpen = false;
                },
            });
        },

        lineTotal(item) {
            return Math.round(item.price_cents * item.qty);
        },

        get subtotalCents() {
            return this.cart.reduce((sum, item) => sum + this.lineTotal(item), 0);
        },

        get discountCents() {
            if (!this.config.isAdmin || !this.discountType) return 0;

            const value = Number(this.discountValue);
            if (!Number.isFinite(value) || value <= 0) return 0;

            if (this.discountType === 'percent') {
                return Math.min(this.subtotalCents, Math.round(this.subtotalCents * Math.min(100, value) / 100));
            }

            return Math.min(this.subtotalCents, Math.round(value * 100));
        },

        get afterDiscountCents() {
            return Math.max(0, this.subtotalCents - this.discountCents);
        },

        get taxCents() {
            if (!this.config.taxEnabled || !this.config.taxRate) return 0;

            if (this.config.taxInclusive) {
                return Math.round(this.afterDiscountCents * this.config.taxRate / (100 + this.config.taxRate));
            }

            return Math.round(this.afterDiscountCents * this.config.taxRate / 100);
        },

        get totalCents() {
            if (!this.config.taxEnabled) return this.afterDiscountCents;

            return this.config.taxInclusive ? this.afterDiscountCents : this.afterDiscountCents + this.taxCents;
        },

        get tenderedCents() {
            const value = Number(this.amountTendered);
            return Number.isFinite(value) ? Math.max(0, Math.round(value * 100)) : 0;
        },

        get changeCents() {
            return Math.max(0, this.tenderedCents - this.totalCents);
        },

        get shortCents() {
            return Math.max(0, this.totalCents - this.tenderedCents);
        },

        get canSubmit() {
            if (this.pendingCheckout) return !this.submitting;
            if (this.submitting || this.cart.length === 0) return false;
            if (this.paymentMethod === 'other' && !this.paymentLabel.trim()) return false;
            return this.tenderedCents >= this.totalCents;
        },

        openCheckout() {
            if (this.cart.length === 0) return;
            if (this.pendingCheckout) {
                this.checkoutOpen = true;
                return;
            }

            this.checkoutError = '';
            if (!this.idempotencyKey) this.idempotencyKey = crypto.randomUUID();

            if (!this.config.paymentMethods.some((method) => method.value === this.paymentMethod)) {
                this.paymentMethod = this.config.paymentMethods[0]?.value ?? 'cash';
            }

            this.referenceNumber = '';
            this.notes = '';
            this.paymentLabel = '';
            this.amountTendered = (this.totalCents / 100).toFixed(2);
            this.checkoutOpen = true;
        },

        selectPaymentMethod(method) {
            if (this.pendingCheckout) return;
            this.paymentMethod = method;
            this.amountTendered = (this.totalCents / 100).toFixed(2);
            this.checkoutError = '';
        },

        setExactAmount() {
            this.amountTendered = (this.totalCents / 100).toFixed(2);
        },

        setCashAmount(amount) {
            this.amountTendered = Number(amount).toFixed(2);
        },

        async submitCheckout() {
            if (!this.canSubmit) return;
            if (!this.idempotencyKey) this.idempotencyKey = crypto.randomUUID();

            this.submitting = true;
            this.checkoutError = '';

            const payload = this.pendingCheckout ? JSON.parse(this.pendingCheckout) : {
                items: this.cart.map((item) => ({ product_id: item.id, quantity: item.qty })),
                payment_method: this.paymentMethod,
                payment_label: this.paymentMethod === 'other' ? this.paymentLabel.trim() : null,
                amount_tendered: this.amountTendered,
                reference_number: this.referenceNumber.trim() || null,
                discount_type: this.config.isAdmin && this.discountType ? this.discountType : null,
                discount_value: this.config.isAdmin && this.discountType ? (this.discountValue || '0') : null,
                notes: this.notes.trim() || null,
                idempotency_key: this.idempotencyKey,
            };
            this.pendingCheckout = JSON.stringify(payload);

            try {
                const data = await window.apiFetch(this.config.checkoutUrl, {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });

                this.receiptHtml = data.receipt_html;
                this.receiptUrl = data.receipt_url;
                this.receiptPdfUrl = data.receipt_pdf_url;
                this.completedSale = data.sale;
                this.checkoutOpen = false;
                this.receiptOpen = true;
                this.mobileCartOpen = false;
                this.cart = [];
                this.idempotencyKey = null;
                this.pendingCheckout = null;
                this.discountType = '';
                this.discountValue = '';
                this.paymentLabel = '';
                this.amountTendered = '';
                this.referenceNumber = '';
                this.notes = '';
                this.fetchProducts(true);
                window.toast('Sale completed successfully.');
            } catch (error) {
                if (error.status === 422 && !error.errors?.idempotency_key) {
                    // A validation rejection is a known non-commit; a network/5xx error is not.
                    this.pendingCheckout = null;
                    this.idempotencyKey = null;
                }
                const firstError = error.errors ? Object.values(error.errors)[0]?.[0] : null;
                this.checkoutError = firstError || error.message || 'The sale could not be completed.';
                if (this.pendingCheckout) this.checkoutError += ' The outcome is uncertain. Retry this unchanged order before starting another sale; do not refresh.';
            } finally {
                this.submitting = false;
            }
        },

        printReceipt() {
            if (!this.receiptUrl) return;
            window.open(`${this.receiptUrl}?autoprint=1`, '_blank', 'noopener');
        },

        checkoutPending() {
            if (!this.pendingCheckout) return false;
            window.toast('This checkout is unresolved. Reopen checkout and retry the original order before making changes.', 'error');
            return true;
        },

        startNewOrder() {
            this.receiptOpen = false;
            this.receiptHtml = '';
            this.receiptUrl = '';
            this.receiptPdfUrl = '';
            this.completedSale = null;
            this.$nextTick(() => this.$refs.search?.focus());
        },

        money(cents) {
            return '₱' + (Math.round(cents) / 100).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },

        formatQty(value) {
            const number = Number(value);
            if (Number.isInteger(number)) return number.toLocaleString('en-PH');
            return number.toLocaleString('en-PH', { maximumFractionDigits: 3 });
        },

        stockLabel(product) {
            if (!product.track_inventory) return 'Made to order';
            if (product.stock_remaining <= 0) return 'Out of stock';
            return `${product.formatted_stock} ${product.unit} left`;
        },
    }));
});

window.toast = (message, type = 'success') => Alpine.store('toasts').push(message, type);
window.confirmAction = (options) => Alpine.store('confirm').ask(options);

Alpine.start();
