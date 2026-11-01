<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $sale->transaction_number }}</title>
    <style>
        @page {
            margin: 4mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        .pdf-sheet {
            width: 100%;
            padding: 2mm;
        }
    </style>
</head>
<body>
    <div class="pdf-sheet">
        @include('partials.receipt-body', ['sale' => $sale])
    </div>
</body>
</html>
