@extends('layouts.app')

@section('title', 'Settings')
@section('eyebrow', 'Business setup')

@section('content')
    @php
        $paymentIcons = [
            'cash' => 'banknote',
            'gcash' => 'wallet',
            'maya' => 'credit-card',
            'other' => 'receipt-text',
        ];
        $selectedMethods = (array) old('payment_methods', $enabledPaymentMethods);
        $taxEnabledOld = old('tax_enabled', $taxEnabled ? '1' : '0') === '1';
        $taxInclusiveOld = old('tax_inclusive', $taxInclusive ? '1' : '0') === '1';
    @endphp

    <div class="mb-6">
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle">Business details, receipt text, stock alerts, payments, and optional tax behavior.</p>
    </div>

    <form
        method="POST"
        action="{{ route('settings.update') }}"
        enctype="multipart/form-data"
        class="space-y-4"
        x-data="{
            taxEnabled: {{ $taxEnabledOld ? 'true' : 'false' }},
            taxInclusive: {{ $taxInclusiveOld ? 'true' : 'false' }},
            preview: @js($logoUrl),
            fileName: '',
            chooseLogo(event) {
                const file = event.target.files?.[0];
                this.fileName = file ? file.name : '';
                if (file) {
                    if (this.preview && this.preview.startsWith('blob:')) URL.revokeObjectURL(this.preview);
                    this.preview = URL.createObjectURL(file);
                }
            }
        }"
    >
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-danger">
                <p class="font-semibold">Some settings could not be saved:</p>
                <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 xl:grid-cols-3">
            <div class="card card-pad xl:col-span-2">
                <h2 class="section-title">Business identity</h2>
                <p class="mt-1 text-xs text-muted">Shown on the dashboard, POS header, and printed receipts.</p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="business_name" class="label">Business name</label>
                        <input id="business_name" name="business_name" type="text" value="{{ old('business_name', setting('business_name')) }}" class="input @error('business_name') input-error @enderror" required maxlength="120">
                        @error('business_name') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="business_address" class="label">Address</label>
                        <textarea id="business_address" name="business_address" rows="2" class="input @error('business_address') input-error @enderror" maxlength="255">{{ old('business_address', setting('business_address')) }}</textarea>
                        @error('business_address') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="business_phone" class="label">Phone</label>
                        <input id="business_phone" name="business_phone" type="text" value="{{ old('business_phone', setting('business_phone')) }}" class="input @error('business_phone') input-error @enderror" maxlength="50" placeholder="0917 123 4567">
                        @error('business_phone') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="business_email" class="label">Email</label>
                        <input id="business_email" name="business_email" type="email" value="{{ old('business_email', setting('business_email')) }}" class="input @error('business_email') input-error @enderror" maxlength="255" placeholder="hello@example.com">
                        @error('business_email') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="card card-pad">
                <h2 class="section-title">Logo</h2>
                <p class="mt-1 text-xs text-muted">Square images work best. JPG, PNG, or WebP up to 2 MB.</p>

                <div class="mt-4 flex aspect-square max-h-44 items-center justify-center overflow-hidden rounded-xl border border-dashed border-line bg-slate-50">
                    <template x-if="preview">
                        <img :src="preview" alt="Current business logo" class="h-full w-full object-contain p-3">
                    </template>
                    <template x-if="!preview">
                        <div class="text-center text-muted">
                            <x-icon name="store" class="mx-auto h-9 w-9" />
                            <p class="mt-2 text-xs">No logo yet</p>
                        </div>
                    </template>
                </div>

                <div class="mt-4">
                    <label for="logo" class="label">Upload logo</label>
                    <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full cursor-pointer rounded-lg border border-line bg-white text-xs text-muted file:mr-3 file:cursor-pointer file:border-0 file:bg-orange-soft file:px-3 file:py-2.5 file:text-xs file:font-semibold file:text-orange-dark hover:file:bg-orange-100" @change="chooseLogo($event)">
                    <p class="mt-1 truncate text-[11px] text-muted" x-show="fileName" x-text="fileName" x-cloak></p>
                    @error('logo') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>

                @if (setting('logo_path'))
                    <label class="mt-3 flex items-center gap-2 text-xs text-muted">
                        <input type="checkbox" name="remove_logo" value="1" class="h-4 w-4 rounded border-line text-orange focus:ring-orange" @checked(old('remove_logo'))>
                        Remove the current logo
                    </label>
                @endif
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <div class="card card-pad">
                <h2 class="section-title">Receipt and stock alerts</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label for="receipt_footer" class="label">Receipt footer</label>
                        <input id="receipt_footer" name="receipt_footer" type="text" value="{{ old('receipt_footer', setting('receipt_footer')) }}" class="input @error('receipt_footer') input-error @enderror" maxlength="255" placeholder="Thank you! Please come again.">
                        <p class="mt-1 text-[11px] text-muted">Printed at the bottom of every receipt.</p>
                        @error('receipt_footer') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="low_stock_threshold" class="label">Low stock warning level</label>
                        <div class="flex items-center gap-2">
                            <input id="low_stock_threshold" name="low_stock_threshold" type="number" inputmode="numeric" min="0" max="10000" step="1" value="{{ old('low_stock_threshold', setting('low_stock_threshold', '5')) }}" class="input w-32 @error('low_stock_threshold') input-error @enderror" required>
                            <span class="text-sm text-muted">units or less</span>
                        </div>
                        <p class="mt-1 text-[11px] text-muted">Products at or below this level appear in the low-stock list. The per-product threshold can still override it.</p>
                        @error('low_stock_threshold') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-3 rounded-xl border border-line bg-slate-50 p-3 sm:grid-cols-2">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wide text-muted">Currency</p>
                            <p class="mt-1 text-sm font-semibold text-ink">PHP · Philippine peso</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wide text-muted">Timezone</p>
                            <p class="mt-1 text-sm font-semibold text-ink">Asia/Manila</p>
                        </div>
                        <p class="text-[11px] leading-relaxed text-muted sm:col-span-2">Currency and timezone are fixed for this karinderya app so sales and receipts stay consistent.</p>
                    </div>
                </div>
            </div>

            <div class="card card-pad">
                <h2 class="section-title">Payment methods</h2>
                <p class="mt-1 text-xs text-muted">Choose what cashiers can select at checkout. At least one method is required.</p>

                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    @foreach ($paymentMethods as $value => $label)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-line bg-white p-3 transition hover:border-orange/40 hover:bg-orange-soft/30">
                            <input type="checkbox" name="payment_methods[]" value="{{ $value }}" class="h-4 w-4 rounded border-line text-orange focus:ring-orange" @checked(in_array($value, $selectedMethods, true))>
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-soft text-orange-dark">
                                <x-icon :name="$paymentIcons[$value]" class="h-4 w-4" />
                            </span>
                            <span>
                                <span class="block text-sm font-semibold text-ink">{{ $label }}</span>
                                <span class="block text-[10px] text-muted">{{ $value === 'other' ? 'Cashier types a label' : 'Recorded at checkout' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('payment_methods') <p class="mt-2 text-xs text-danger">{{ $message }}</p> @enderror
                <p class="mt-3 flex items-start gap-2 rounded-lg bg-slate-50 p-3 text-[11px] leading-relaxed text-muted">
                    <x-icon name="alert-circle" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                    <span>Digital payments are recorded for tracking only. The app does not verify GCash, Maya, or bank payments.</span>
                </p>
            </div>
        </div>

        <div class="card card-pad">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="section-title">Optional tax</h2>
                    <p class="mt-1 max-w-2xl text-xs leading-relaxed text-muted">Leave this off unless you already know the correct tax treatment for the business. This is a simple internal calculation, not a BIR-accredited sales system.</p>
                </div>
                <label class="flex shrink-0 cursor-pointer items-center gap-2 text-sm font-medium text-ink">
                    <input type="hidden" name="tax_enabled" value="0">
                    <input type="checkbox" name="tax_enabled" value="1" x-model="taxEnabled" class="h-4 w-4 rounded border-line text-orange focus:ring-orange">
                    Enable tax
                </label>
            </div>

            <div x-show="taxEnabled" x-cloak class="mt-4 grid gap-4 border-t border-line pt-4 sm:grid-cols-2">
                <div>
                    <label for="tax_rate" class="label">Tax rate (%)</label>
                    <div class="relative">
                        <input id="tax_rate" name="tax_rate" type="number" inputmode="decimal" min="0.01" max="100" step="0.01" value="{{ old('tax_rate', $taxRate) }}" class="input pr-9 @error('tax_rate') input-error @enderror">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-muted">%</span>
                    </div>
                    @error('tax_rate') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <span class="label">How tax is applied</span>
                    <div class="space-y-2">
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-line px-3 py-2.5 text-sm">
                            <input type="hidden" name="tax_inclusive" value="0">
                            <input type="checkbox" name="tax_inclusive" value="1" x-model="taxInclusive" class="h-4 w-4 rounded border-line text-orange focus:ring-orange">
                            Prices already include tax
                        </label>
                        <p class="text-[11px] leading-relaxed text-muted" x-text="taxInclusive ? 'The receipt shows the tax portion inside the listed price.' : 'Tax is added on top of the subtotal after discounts.'"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <x-icon name="check" class="h-4 w-4" /> Save settings
            </button>
        </div>
    </form>
@endsection
