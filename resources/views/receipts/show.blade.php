<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $sale->transaction_number }} · {{ setting('business_name', config('app.name')) }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page {
            size: {{ $width }}mm auto;
            margin: 3mm;
        }

        .receipt-sheet {
            width: {{ $width }}mm;
            max-width: 100%;
            padding: {{ $width === '58' ? '4mm' : '6mm' }};
            background: #ffffff;
        }

        @media print {
            @page {
                size: {{ $width }}mm auto;
                margin: 3mm;
            }

            html, body {
                background: #ffffff !important;
            }

            .no-print {
                display: none !important;
            }

            .print-wrap {
                padding: 0 !important;
                background: #ffffff !important;
            }

            .receipt-sheet {
                width: auto !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: 0 !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 font-sans text-ink">
    <div class="no-print border-b border-line bg-white">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center gap-2 px-4 py-3 sm:px-6">
            <a href="{{ \Illuminate\Support\Facades\Route::has('sales.index') ? route('sales.index') : route('pos.index') }}" class="btn btn-ghost btn-sm">
                <x-icon name="arrow-left" class="h-4 w-4" /> Back
            </a>
            <div class="mx-1 h-5 w-px bg-line"></div>
            <div class="mr-auto">
                <p class="text-sm font-semibold">{{ $sale->transaction_number }}</p>
                <p class="text-xs text-muted">{{ $sale->sold_at->format('M j, Y · g:i A') }}</p>
            </div>

            <div class="inline-flex rounded-lg border border-line bg-white p-0.5">
                <a href="{{ route('receipts.show', ['sale' => $sale, 'width' => '58']) }}" class="rounded-md px-2.5 py-1.5 text-xs font-semibold {{ $width === '58' ? 'bg-orange text-white' : 'text-muted hover:bg-slate-50' }}">58mm</a>
                <a href="{{ route('receipts.show', ['sale' => $sale, 'width' => '80']) }}" class="rounded-md px-2.5 py-1.5 text-xs font-semibold {{ $width === '80' ? 'bg-orange text-white' : 'text-muted hover:bg-slate-50' }}">80mm</a>
            </div>

            <a href="{{ route('receipts.pdf', $sale) }}" class="btn btn-secondary btn-sm">
                <x-icon name="download" class="h-4 w-4" /> PDF
            </a>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                <x-icon name="printer" class="h-4 w-4" /> Print
            </button>
            <a href="{{ route('pos.index') }}" class="btn btn-ghost btn-sm">
                <x-icon name="shopping-cart" class="h-4 w-4" /> New order
            </a>
        </div>
    </div>

    <div class="print-wrap flex justify-center px-4 py-8 sm:py-12">
        <div class="receipt-sheet rounded-lg border border-line shadow-sm">
            @include('partials.receipt-body', ['sale' => $sale])
        </div>
    </div>

    @if ($autoprint)
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>
</html>
