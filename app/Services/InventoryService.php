<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function adjust(Product $product, User $actor, string $type, string $quantity, ?string $reason): InventoryMovement
    {
        if (! $product->track_inventory) {
            throw ValidationException::withMessages([
                'quantity' => 'Stock tracking is turned off for this product.',
            ]);
        }

        return DB::transaction(function () use ($product, $actor, $type, $quantity, $reason) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            if (! $locked->track_inventory) {
                throw ValidationException::withMessages(['quantity' => 'Stock tracking is turned off for this product.']);
            }
            validator(['quantity' => $quantity], [
                'quantity' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999', $locked->requiresWholeQuantity() ? 'multiple_of:1' : 'numeric'],
            ])->validate();
            $before = (float) $locked->stock_quantity;
            $amount = (float) $quantity;

            $after = match ($type) {
                InventoryMovement::TYPE_STOCK_IN => $before + $amount,
                InventoryMovement::TYPE_STOCK_OUT => $before - $amount,
                InventoryMovement::TYPE_ADJUSTMENT => $amount,
                default => throw ValidationException::withMessages(['type' => 'Unknown adjustment type.']),
            };

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Not enough stock. You only have '.quantity($before).' '.$locked->unit.' on hand.',
                ]);
            }

            $locked->update(['stock_quantity' => number_format($after, 3, '.', '')]);

            $movement = InventoryMovement::query()->create([
                'product_id' => $locked->id,
                'user_id' => $actor->id,
                'type' => $type,
                'quantity_change' => number_format($after - $before, 3, '.', ''),
                'quantity_before' => number_format($before, 3, '.', ''),
                'quantity_after' => number_format($after, 3, '.', ''),
                'reason' => $reason,
            ]);

            AuditLog::record(
                $actor,
                'inventory.adjusted',
                'Adjusted stock for '.$locked->name.' ('.$type.') from '.quantity($before).' to '.quantity($after),
                $locked,
                ['stock_quantity' => number_format($before, 3, '.', '')],
                ['stock_quantity' => number_format($after, 3, '.', ''), 'reason' => $reason]
            );

            return $movement;
        });
    }
}
