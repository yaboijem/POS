<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReceiptController extends Controller
{
    public function show(Request $request, Sale $sale): View
    {
        Gate::authorize('view', $sale);

        $sale->load(['items', 'payment', 'cashier']);

        return view('receipts.show', [
            'sale' => $sale,
            'width' => $request->query('width') === '58' ? '58' : '80',
            'autoprint' => $request->boolean('autoprint'),
        ]);
    }

    public function pdf(Sale $sale): Response
    {
        Gate::authorize('view', $sale);

        $sale->load(['items', 'payment', 'cashier']);

        $pdf = Pdf::loadView('receipts.pdf', ['sale' => $sale])
            ->setPaper([0, 0, 226.77, 900], 'portrait');

        return $pdf->download('receipt-'.$sale->transaction_number.'.pdf');
    }
}
