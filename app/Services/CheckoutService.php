<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\InventoryMovement;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /**
     * Complete a sale. All money is recalculated here; client totals are ignored.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws ValidationException
     */
    public function checkout(User $cashier, array $payload): Sale
    {
        $hash = $this->payloadHash($payload);

        try {
            return DB::transaction(function () use ($cashier, $payload, $hash) {
                // Serialize this cashier's attempts, then use a current read before stock checks.
                User::query()->whereKey($cashier->id)->lockForUpdate()->firstOrFail();
                $existing = Sale::query()->where('idempotency_key', $payload['idempotency_key'])->lockForUpdate()->first();
                if ($existing) {
                    return $this->resolveExisting($existing, $cashier, $hash);
                }
                $lines = [];
                $firstIndex = [];

                foreach ($payload['items'] as $index => $item) {
                    $productId = (int) $item['product_id'];
                    $quantity = (float) $item['quantity'];
                    validator(['quantity' => $item['quantity']], ['quantity' => ['required', 'numeric', 'decimal:0,3', 'min:0.001', 'max:9999']])->validate();
                    $firstIndex[$productId] ??= $index;

                    if (isset($lines[$productId])) {
                        $lines[$productId]['quantity'] += $quantity;
                    } else {
                        $lines[$productId] = ['product_id' => $productId, 'quantity' => $quantity];
                    }
                }

                $products = Product::query()
                    ->with('category')
                    ->whereIn('id', array_keys($lines))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $subtotalCents = 0;
                $prepared = [];

                foreach ($lines as $productId => $line) {
                    $index = $firstIndex[$productId];
                    $product = $products->get($productId);
                    $quantity = $line['quantity'];

                    if (! $product) {
                        throw ValidationException::withMessages([
                            "items.$index.product_id" => 'This product is no longer available.',
                        ]);
                    }

                    if (! $product->is_available || ! $product->category?->is_active) {
                        throw ValidationException::withMessages([
                            "items.$index.product_id" => $product->name.' is not available for sale right now.',
                        ]);
                    }

                    if ($product->requiresWholeQuantity() && $quantity !== floor($quantity)) {
                        throw ValidationException::withMessages([
                            "items.$index.quantity" => $product->name.' is sold in whole '.$product->unit.'s only.',
                        ]);
                    }

                    if ($product->track_inventory && $quantity > (float) $product->stock_quantity + 0.0005) {
                        throw ValidationException::withMessages([
                            "items.$index.quantity" => 'Only '.quantity($product->stock_quantity).' '.$product->unit.' of '.$product->name.' left in stock.',
                        ]);
                    }

                    $unitCents = to_cents($product->selling_price);
                    $lineCents = (int) round($unitCents * $quantity);
                    $subtotalCents += $lineCents;

                    $prepared[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'unit_cents' => $unitCents,
                        'line_cents' => $lineCents,
                    ];
                }

                $discountType = $payload['discount_type'] ?? null;
                $discountValue = isset($payload['discount_value']) ? (float) $payload['discount_value'] : null;
                $discountCents = 0;

                if ($discountType) {
                    if (! $cashier->isAdmin()) {
                        throw ValidationException::withMessages([
                            'discount_type' => 'Only an administrator can apply a discount.',
                        ]);
                    }

                    if ($discountType === 'percent') {
                        if ($discountValue === null || $discountValue < 0 || $discountValue > 100) {
                            throw ValidationException::withMessages([
                                'discount_value' => 'The discount percentage must be between 0 and 100.',
                            ]);
                        }

                        $discountCents = (int) round($subtotalCents * $discountValue / 100);
                    } else {
                        $discountCents = min($subtotalCents, to_cents($discountValue ?? 0));
                    }

                    $discountCents = max(0, min($discountCents, $subtotalCents));
                }

                $afterDiscountCents = $subtotalCents - $discountCents;

                $taxEnabled = (string) setting('tax_enabled', '0') === '1';
                $taxRate = (float) setting('tax_rate', 0);
                $taxInclusive = (string) setting('tax_inclusive', '0') === '1';
                $taxCents = 0;
                $totalCents = $afterDiscountCents;

                if ($taxEnabled && $taxRate > 0) {
                    if ($taxInclusive) {
                        $taxCents = (int) round($afterDiscountCents * $taxRate / (100 + $taxRate));
                        $totalCents = $afterDiscountCents;
                    } else {
                        $taxCents = (int) round($afterDiscountCents * $taxRate / 100);
                        $totalCents = $afterDiscountCents + $taxCents;
                    }
                }

                $tenderedCents = to_cents($payload['amount_tendered']);
                $method = $payload['payment_method'];
                $changeCents = 0;

                if ($tenderedCents < $totalCents) {
                    throw ValidationException::withMessages([
                        'amount_tendered' => 'The amount received must cover the total of '.peso(from_cents($totalCents)).'.',
                    ]);
                }

                if ($method === 'cash') {
                    $changeCents = $tenderedCents - $totalCents;
                }

                $reference = (string) Str::ulid();
                $date = now();

                do {
                    $transactionNumber = 'KAR-'.$date->format('Ymd').'-'.Str::upper(Str::random(5));
                } while (Sale::query()->where('transaction_number', $transactionNumber)->exists());

                $sale = Sale::query()->create([
                    'reference' => $reference,
                    'transaction_number' => $transactionNumber,
                    'cashier_id' => $cashier->id,
                    'status' => Sale::STATUS_COMPLETED,
                    'subtotal' => from_cents($subtotalCents),
                    'discount_type' => $discountType,
                    'discount_value' => $discountType ? ($discountType === 'percent' ? number_format((float) $discountValue, 2, '.', '') : from_cents(to_cents($discountValue ?? 0))) : null,
                    'discount_amount' => from_cents($discountCents),
                    'tax_amount' => from_cents($taxCents),
                    'total' => from_cents($totalCents),
                    'amount_tendered' => from_cents($tenderedCents),
                    'change_amount' => from_cents($changeCents),
                    'payment_method' => $method,
                    'payment_label' => $method === 'other' ? ($payload['payment_label'] ?? null) : null,
                    'payment_reference' => $payload['reference_number'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                    'idempotency_key' => $payload['idempotency_key'],
                    'checkout_hash' => $hash,
                    'sold_at' => $date,
                ]);

                foreach ($prepared as $line) {
                    /** @var Product $product */
                    $product = $line['product'];

                    SaleItem::query()->create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'category_name' => $product->category->name,
                        'category_color' => $product->category->color,
                        'sku' => $product->sku,
                        'unit' => $product->unit,
                        'unit_price' => from_cents($line['unit_cents']),
                        'quantity' => number_format($line['quantity'], 3, '.', ''),
                        'line_total' => from_cents($line['line_cents']),
                        'cost_price' => $product->cost_price,
                    ]);

                    if ($product->track_inventory) {
                        $before = (float) $product->stock_quantity;
                        $after = $before - $line['quantity'];

                        $product->update(['stock_quantity' => number_format($after, 3, '.', '')]);

                        InventoryMovement::query()->create([
                            'product_id' => $product->id,
                            'user_id' => $cashier->id,
                            'sale_id' => $sale->id,
                            'type' => InventoryMovement::TYPE_SALE,
                            'quantity_change' => number_format(-$line['quantity'], 3, '.', ''),
                            'quantity_before' => number_format($before, 3, '.', ''),
                            'quantity_after' => number_format($after, 3, '.', ''),
                            'reason' => 'Sold on '.$transactionNumber,
                        ]);
                    }
                }

                Payment::query()->create([
                    'sale_id' => $sale->id,
                    'method' => $method,
                    'payment_label' => $method === 'other' ? ($payload['payment_label'] ?? null) : null,
                    'reference_number' => $payload['reference_number'] ?? null,
                    'amount' => from_cents($tenderedCents),
                    'status' => 'recorded',
                    'paid_at' => $date,
                ]);

                AuditLog::record(
                    $cashier,
                    'sale.completed',
                    'Completed '.$transactionNumber.' for '.peso(from_cents($totalCents)),
                    $sale,
                    [],
                    ['total' => from_cents($totalCents), 'payment_method' => $method, 'items' => count($prepared)]
                );

                return $sale->load(['items', 'payment', 'cashier']);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $existing = Sale::query()
                ->where('idempotency_key', $payload['idempotency_key'])
                ->first();

            if ($existing) {
                return $this->resolveExisting($existing, $cashier, $hash);
            }

            throw $exception;
        }
    }

    private function resolveExisting(Sale $sale, User $cashier, string $hash): Sale
    {
        if ($sale->cashier_id !== $cashier->id || ! $sale->checkout_hash || ! hash_equals($sale->checkout_hash, $hash)) {
            throw ValidationException::withMessages(['idempotency_key' => 'This checkout key belongs to a different order. Check sales history before starting another sale.']);
        }

        return $sale->load(['items', 'payment', 'cashier']);
    }

    private function payloadHash(array $payload): string
    {
        $items = collect($payload['items'])->map(fn ($item) => [
            'product_id' => (int) $item['product_id'],
            'quantity' => number_format((float) $item['quantity'], 3, '.', ''),
        ])->sortBy('product_id')->values()->all();

        return hash('sha256', json_encode([
            'items' => $items,
            'payment_method' => $payload['payment_method'],
            'payment_label' => $payload['payment_label'] ?? null,
            'amount_tendered' => to_cents($payload['amount_tendered']),
            'reference_number' => $payload['reference_number'] ?? null,
            'discount_type' => $payload['discount_type'] ?? null,
            'discount_value' => isset($payload['discount_value']) ? number_format((float) $payload['discount_value'], 2, '.', '') : null,
            'notes' => $payload['notes'] ?? null,
        ], JSON_THROW_ON_ERROR));
    }
}
