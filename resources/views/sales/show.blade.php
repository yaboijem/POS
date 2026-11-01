@extends(auth()->user()->isAdmin() ? 'layouts.app' : 'layouts.pos')

@section('title', 'Transaction '.$sale->transaction_number)
@section('eyebrow', 'Transaction details')

@section('content')
    @php
        $isAdmin = auth()->user()->isAdmin();
        $discountLabel = $sale->discount_type === 'percent'
            ? 'Discount ('.rtrim(rtrim(number_format((float) $sale->discount_value, 2, '.', ''), '0'), '.').'%)'
            : 'Discount';
        $itemCount = (float) $sale->items->sum('quantity');
    @endphp

    <div class="{{ $isAdmin ? '' : 'mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8' }}">
        <div class="mb-6">
            <a href="{{ route('sales.index') }}" class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-ink">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back to sales
            </a>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="num page-title">{{ $sale->transaction_number }}</h1>
                        <span class="badge badge-success">Completed</span>
                    </div>
                    <p class="page-subtitle">{{ $sale->sold_at->format('l, F j, Y · g:i A') }} · {{ $sale->cashier?->name ?? 'Deleted user' }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('receipts.show', ['sale' => $sale, 'autoprint' => 1]) }}" target="_blank" rel="noopener" class="btn btn-primary">
                        <x-icon name="printer" class="h-4 w-4" /> Print receipt
                    </a>
                    <a href="{{ route('receipts.pdf', $sale) }}" class="btn btn-secondary">
                        <x-icon name="download" class="h-4 w-4" /> PDF
                    </a>
                    <a href="{{ route('receipts.show', $sale) }}" class="btn btn-ghost">View receipt</a>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="card p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-muted">Sale total</p>
                <p class="num mt-2 font-display text-2xl font-bold text-ink">{{ peso($sale->total) }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-muted">Payment</p>
                <p class="mt-2 text-sm font-semibold text-ink">{{ payment_method_label($sale->payment_method, $sale->payment_label) }}</p>
                <p class="mt-1 text-xs text-muted">{{ $sale->payment?->status === 'recorded' ? 'Recorded' : 'No payment record' }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-muted">Items sold</p>
                <p class="num mt-2 font-display text-2xl font-bold text-ink">{{ quantity($itemCount) }}</p>
                <p class="mt-1 text-xs text-muted">{{ $sale->items->count() }} {{ \Illuminate\Support\Str::plural('line', $sale->items->count()) }}</p>
            </div>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-3">
            <div class="space-y-4 xl:col-span-2">
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
                        <div>
                            <h2 class="section-title">Items</h2>
                            <p class="mt-0.5 text-xs text-muted">Product names and prices are kept exactly as they were sold.</p>
                        </div>
                        <span class="badge badge-orange">{{ quantity($itemCount) }} items</span>
                    </div>

                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Unit price</th>
                                    <th class="text-right">Line total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sale->items as $item)
                                    <tr>
                                        <td>
                                            <p class="font-medium text-ink">{{ $item->product_name }}</p>
                                            @if ($item->sku)
                                                <p class="num mt-0.5 text-[11px] text-muted">{{ $item->sku }}</p>
                                            @endif
                                        </td>
                                        <td class="num whitespace-nowrap">{{ quantity($item->quantity) }} {{ $item->unit }}</td>
                                        <td class="num whitespace-nowrap">{{ peso($item->unit_price) }}</td>
                                        <td class="num whitespace-nowrap text-right font-semibold">{{ peso($item->line_total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-line bg-slate-50/50 px-5 py-4">
                        <dl class="ml-auto max-w-sm space-y-2 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-muted">Subtotal</dt>
                                <dd class="num font-medium text-ink">{{ peso($sale->subtotal) }}</dd>
                            </div>
                            @if ((float) $sale->discount_amount > 0)
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-muted">{{ $discountLabel }}</dt>
                                    <dd class="num font-medium text-emerald-700">−{{ peso($sale->discount_amount) }}</dd>
                                </div>
                            @endif
                            @if ((float) $sale->tax_amount > 0)
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-muted">Tax</dt>
                                    <dd class="num font-medium text-ink">{{ peso($sale->tax_amount) }}</dd>
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-4 border-t border-line pt-2">
                                <dt class="font-semibold text-ink">Total</dt>
                                <dd class="num text-lg font-bold text-ink">{{ peso($sale->total) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-muted">Amount received</dt>
                                <dd class="num font-medium text-ink">{{ peso($sale->amount_tendered) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-muted">Change</dt>
                                <dd class="num font-medium text-ink">{{ peso($sale->change_amount) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-900">
                    <x-icon name="alert-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                    <p>Completed sales are permanent records. This version does not support voids, refunds, or editing a completed transaction.</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="card card-pad">
                    <h2 class="section-title">Payment details</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-muted">Method</dt>
                            <dd class="text-right font-semibold text-ink">{{ payment_method_label($sale->payment_method, $sale->payment_label) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-muted">Status</dt>
                            <dd><span class="badge badge-success">{{ $sale->payment?->status === 'recorded' ? 'Recorded' : 'Missing' }}</span></dd>
                        </div>
                        @if ($sale->payment_reference)
                            <div>
                                <dt class="text-muted">Reference</dt>
                                <dd class="mt-1 break-all font-medium text-ink">{{ $sale->payment_reference }}</dd>
                            </div>
                        @endif
                        @if ($sale->notes)
                            <div>
                                <dt class="text-muted">Note</dt>
                                <dd class="mt-1 text-ink">{{ $sale->notes }}</dd>
                            </div>
                        @endif
                        <div class="border-t border-line pt-3">
                            <dt class="text-muted">Sale reference</dt>
                            <dd class="num mt-1 break-all text-[11px] text-ink">{{ $sale->reference }}</dd>
                        </div>
                    </dl>
                    <p class="mt-4 text-[11px] leading-relaxed text-muted">Digital payments are recorded for tracking only. They are not verified with GCash, Maya, or a bank.</p>
                </div>

                <div class="card card-pad">
                    <h2 class="section-title">Activity</h2>
                    @if ($auditLogs->isEmpty())
                        <p class="mt-3 text-sm text-muted">No activity entries were recorded for this sale.</p>
                    @else
                        <ol class="mt-4 space-y-3">
                            @foreach ($auditLogs as $log)
                                <li class="border-l-2 border-orange/30 pl-3">
                                    <p class="text-sm font-medium text-ink">{{ $log->description }}</p>
                                    <p class="mt-0.5 text-[11px] text-muted">
                                        {{ $log->user?->name ?? 'System' }} · {{ $log->created_at->format('M j, Y g:i A') }}
                                    </p>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
