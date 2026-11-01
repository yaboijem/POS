<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const UNITS = [
        'piece', 'bottle', 'can', 'pack', 'serving', 'order', 'kilogram',
    ];

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'description',
        'image_path',
        'selling_price',
        'cost_price',
        'unit',
        'track_inventory',
        'stock_quantity',
        'low_stock_threshold',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock_quantity' => 'decimal:3',
            'low_stock_threshold' => 'decimal:3',
            'track_inventory' => 'boolean',
            'is_available' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (blank($product->sku)) {
                $product->sku = static::generateSku($product->name);
            }
        });
    }

    public static function generateSku(string $name): string
    {
        $base = Str::upper(Str::slug($name, '-')) ?: 'PRODUCT';
        $base = Str::limit($base, 18, '');
        $sku = $base.'-'.Str::upper(Str::random(4));

        while (static::withTrashed()->where('sku', $sku)->exists()) {
            $sku = $base.'-'.Str::upper(Str::random(4));
        }

        return $sku;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function scopeTracked(Builder $query): Builder
    {
        return $query->where('track_inventory', true);
    }

    public function requiresWholeQuantity(): bool
    {
        return $this->unit !== 'kilogram';
    }

    public function isLowStock(): bool
    {
        if (! $this->track_inventory) {
            return false;
        }

        $threshold = $this->low_stock_threshold ?? setting('low_stock_threshold', 5);

        return (float) $this->stock_quantity <= (float) $threshold;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
