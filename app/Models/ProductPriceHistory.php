<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'changed_by',
        'old_selling_price',
        'new_selling_price',
        'old_cost_price',
        'new_cost_price',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'old_selling_price' => 'decimal:2',
            'new_selling_price' => 'decimal:2',
            'old_cost_price' => 'decimal:2',
            'new_cost_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
