<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustStockRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $lowOnly = $request->boolean('low_stock');
        $defaultThreshold = (float) setting('low_stock_threshold', 5);

        $products = Product::query()
            ->tracked()
            ->with('category')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%');
                });
            })
            ->when($lowOnly, fn ($query) => $query
                ->whereColumn('stock_quantity', '<=', DB::raw('COALESCE(low_stock_threshold, '.$defaultThreshold.')')))
            ->orderBy('stock_quantity')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $trackedQuery = Product::query()->tracked();

        return view('inventory.index', [
            'products' => $products,
            'search' => $search,
            'lowOnly' => $lowOnly,
            'trackedCount' => (clone $trackedQuery)->count(),
            'lowCount' => (clone $trackedQuery)
                ->whereColumn('stock_quantity', '<=', DB::raw('COALESCE(low_stock_threshold, '.$defaultThreshold.')'))
                ->count(),
            'outCount' => (clone $trackedQuery)->where('stock_quantity', '<=', 0)->count(),
            'stockValue' => (float) Product::query()->tracked()
                ->selectRaw('COALESCE(SUM(stock_quantity * COALESCE(cost_price, 0)), 0) AS value')
                ->value('value'),
            'defaultThreshold' => $defaultThreshold,
        ]);
    }

    public function movements(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search')),
            'type' => $request->query('type'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        $movements = InventoryMovement::query()
            ->with(['product', 'user', 'sale'])
            ->when($filters['search'], fn ($query, $search) => $query->whereHas('product', function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('sku', 'like', '%'.$search.'%');
            }))
            ->when($filters['type'], fn ($query, $type) => $query->where('type', $type))
            ->when($filters['from'], fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('inventory.movements', compact('movements', 'filters'));
    }

    public function adjust(AdjustStockRequest $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        $movement = $inventory->adjust(
            $product,
            $request->user(),
            $request->validated('type'),
            (string) $request->validated('quantity'),
            $request->validated('reason'),
        );

        return back()->with('success', 'Stock updated. '.$product->name.' now has '.quantity($movement->quantity_after).' '.$product->unit.' on hand.');
    }
}
