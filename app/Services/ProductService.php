<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\User;

class ProductService
{
    public function updatePrice(Product $product, User $actor, string $newPrice, ?string $note = null): bool
    {
        $oldPrice = (string) $product->selling_price;
        $oldCost = $product->cost_price;

        if ((float) $oldPrice === (float) $newPrice) {
            return false;
        }

        $product->update(['selling_price' => $newPrice]);
        $this->recordPriceChange($product, $actor, $oldPrice, $oldCost, $note);

        return true;
    }

    public function recordPriceChange(Product $product, User $actor, string $oldPrice, ?string $oldCost, ?string $note = null): void
    {
        if ((float) $oldPrice === (float) $product->selling_price && (float) $oldCost === (float) $product->cost_price) {
            return;
        }

        ProductPriceHistory::query()->create([
            'product_id' => $product->id,
            'changed_by' => $actor->id,
            'old_selling_price' => $oldPrice,
            'new_selling_price' => $product->selling_price,
            'old_cost_price' => $oldCost,
            'new_cost_price' => $product->cost_price,
            'note' => $note,
        ]);

        AuditLog::record(
            $actor,
            'product.price_updated',
            'Updated price of '.$product->name.' from '.peso($oldPrice).' to '.peso($product->selling_price),
            $product,
            ['selling_price' => $oldPrice, 'cost_price' => $oldCost],
            ['selling_price' => $product->selling_price, 'cost_price' => $product->cost_price]
        );
    }
}
