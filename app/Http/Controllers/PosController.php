<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->withCount(['products' => fn ($query) => $query->where('is_available', true)])
            ->get();

        $totalProducts = Product::query()
            ->available()
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->count();

        return view('pos.index', [
            'categories' => $categories,
            'config' => [
                'productsUrl' => route('pos.products'),
                'checkoutUrl' => route('pos.checkout'),
                'categories' => $categories->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'color' => $category->color,
                    'count' => $category->products_count,
                ])->values(),
                'totalProducts' => $totalProducts,
                'paymentMethods' => collect(enabled_payment_methods())->map(fn ($method) => [
                    'value' => $method,
                    'label' => payment_method_label($method),
                ])->values(),
                'taxEnabled' => (string) setting('tax_enabled', '0') === '1' && (float) setting('tax_rate', 0) > 0,
                'taxRate' => (float) setting('tax_rate', 0),
                'taxInclusive' => (string) setting('tax_inclusive', '0') === '1',
                'isAdmin' => auth()->user()->isAdmin(),
            ],
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search'));
        $categoryId = $request->query('category');

        $products = Product::query()
            ->with('category')
            ->available()
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%');
                });
            })
            ->when($categoryId, fn ($query, $category) => $query->where('category_id', $category))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return response()->json([
            'data' => $products->getCollection()->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category?->name,
                'category_color' => $product->category?->color,
                'price' => (string) $product->selling_price,
                'price_cents' => to_cents($product->selling_price),
                'formatted_price' => peso($product->selling_price),
                'unit' => $product->unit,
                'whole_quantity' => $product->requiresWholeQuantity(),
                'image_url' => $product->image_url,
                'track_inventory' => $product->track_inventory,
                'stock_remaining' => $product->track_inventory ? max(0, (float) $product->stock_quantity) : null,
                'formatted_stock' => $product->track_inventory ? quantity($product->stock_quantity) : null,
                'is_low_stock' => $product->isLowStock(),
                'is_available' => $product->is_available,
            ])->values(),
            'current_page' => $products->currentPage(),
            'next_page' => $products->currentPage() < $products->lastPage() ? $products->currentPage() + 1 : null,
            'total' => $products->total(),
        ]);
    }

    public function checkout(CheckoutRequest $request, CheckoutService $checkout): JsonResponse
    {
        $sale = $checkout->checkout($request->user(), $request->validated());

        return response()->json([
            'message' => 'Sale completed.',
            'sale' => [
                'reference' => $sale->reference,
                'transaction_number' => $sale->transaction_number,
                'subtotal' => (string) $sale->subtotal,
                'discount' => (string) $sale->discount_amount,
                'tax' => (string) $sale->tax_amount,
                'total' => (string) $sale->total,
                'amount_tendered' => (string) $sale->amount_tendered,
                'change' => (string) $sale->change_amount,
                'payment_method' => $sale->payment_method,
                'payment_label' => $sale->payment_label,
                'sold_at' => $sale->sold_at->toIso8601String(),
            ],
            'receipt_url' => route('receipts.show', $sale),
            'receipt_pdf_url' => route('receipts.pdf', $sale),
            'receipt_html' => view('partials.receipt-body', ['sale' => $sale])->render(),
        ]);
    }
}
