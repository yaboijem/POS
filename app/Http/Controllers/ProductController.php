<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductPriceRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search')),
            'category' => $request->query('category'),
            'status' => $request->query('status'),
            'stock' => $request->query('stock'),
            'sort' => $request->query('sort', 'recent'),
        ];

        [$sortColumn, $sortDirection] = match ($filters['sort']) {
            'name_asc' => ['name', 'asc'],
            'name_desc' => ['name', 'desc'],
            'price_asc' => ['selling_price', 'asc'],
            'price_desc' => ['selling_price', 'desc'],
            'stock_asc' => ['stock_quantity', 'asc'],
            'stock_desc' => ['stock_quantity', 'desc'],
            default => ['updated_at', 'desc'],
        };

        $defaultThreshold = (float) setting('low_stock_threshold', 5);

        $products = Product::query()
            ->with('category')
            ->when($filters['search'], function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['category'], fn ($query, $category) => $query->where('category_id', $category))
            ->when($filters['status'] === 'available', fn ($query) => $query->where('is_available', true))
            ->when($filters['status'] === 'unavailable', fn ($query) => $query->where('is_available', false))
            ->when($filters['stock'] === 'out', fn ($query) => $query->where('track_inventory', true)->where('stock_quantity', '<=', 0))
            ->when($filters['stock'] === 'low', fn ($query) => $query
                ->where('track_inventory', true)
                ->whereColumn('stock_quantity', '<=', DB::raw('COALESCE(low_stock_threshold, '.$defaultThreshold.')')))
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::query()->ordered()->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'product' => new Product([
                'unit' => 'piece',
                'track_inventory' => false,
                'is_available' => true,
                'stock_quantity' => 0,
            ]),
            'categories' => Category::query()->ordered()->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['sku'] = filled($data['sku'] ?? null) ? strtoupper(trim($data['sku'])) : null;
        $data['track_inventory'] = $request->boolean('track_inventory');
        $data['is_available'] = $request->boolean('is_available');
        $data['image_path'] = $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;
        unset($data['image']);

        $product = Product::query()->create($data);
        AuditLog::record($request->user(), 'product.created', 'Created product '.$product->name, $product, [], $product->only(['name', 'sku', 'selling_price', 'stock_quantity', 'is_available']));

        return redirect()->route('products.index')->with('success', 'Product "'.$product->name.'" was added.');
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product->load('category'),
            'categories' => Category::query()->ordered()->get(),
            'priceHistories' => $product->priceHistories()->with('changer')->latest()->limit(10)->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, ProductService $pricing): RedirectResponse
    {
        return DB::transaction(function () use ($request, $product, $pricing) {
            $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            if ($request->input('unit') !== 'kilogram' && (float) $product->stock_quantity !== floor((float) $product->stock_quantity)) {
                throw ValidationException::withMessages(['unit' => 'Adjust fractional stock in Inventory before choosing a whole-quantity unit.']);
            }
            $old = $product->only(['name', 'sku', 'selling_price', 'cost_price', 'stock_quantity', 'is_available', 'category_id']);
            $oldPrice = (string) $product->selling_price;
            $oldCost = $product->cost_price;

            $data = $request->validated();
            $data['sku'] = filled($data['sku'] ?? null) ? strtoupper(trim($data['sku'])) : $product->sku;
            $data['track_inventory'] = $request->boolean('track_inventory');
            $data['is_available'] = $request->boolean('is_available');

            if ($request->hasFile('image')) {
                $newPath = $request->file('image')->store('products', 'public');
                if ($product->image_path) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $data['image_path'] = $newPath;
            }
            unset($data['image']);

            $product->update($data);
            $pricing->recordPriceChange($product, $request->user(), $oldPrice, $oldCost, 'Updated from product form');

            AuditLog::record($request->user(), 'product.updated', 'Updated product '.$product->name, $product, $old, $product->only(array_keys($old)));

            return redirect()->route('products.index')->with('success', 'Product updated.');
        }, 3);
    }

    public function updatePrice(UpdateProductPriceRequest $request, Product $product, ProductService $pricing): RedirectResponse
    {
        $changed = $pricing->updatePrice($product, $request->user(), (string) $request->validated('selling_price'), $request->validated('note'));

        return back()->with(
            $changed ? 'success' : 'info',
            $changed ? 'Price updated. The POS will use it immediately.' : 'That is already the current price.'
        );
    }

    public function toggleAvailability(Request $request, Product $product): RedirectResponse
    {
        $wasAvailable = $product->is_available;
        $product->update(['is_available' => ! $wasAvailable]);

        AuditLog::record($request->user(), 'product.availability_changed', ($product->is_available ? 'Marked available: ' : 'Marked unavailable: ').$product->name, $product, ['is_available' => $wasAvailable], ['is_available' => $product->is_available]);

        return back()->with('success', $product->is_available ? 'Product is now available on the POS.' : 'Product hidden from the POS.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        AuditLog::record($request->user(), 'product.archived', 'Archived product '.$product->name, $product, $product->only(['name', 'sku', 'selling_price']));
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product archived. Historical sales are unchanged.');
    }
}
