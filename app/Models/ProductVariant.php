<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'variant_name',
        'unit',
        'price_per_unit',
        'min_quantity',
        'step_quantity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_unit' => 'decimal:2',
            'min_quantity' => 'decimal:2',
            'step_quantity' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function label(): string
    {
        return "{$this->product->name} — {$this->variant_name}";
    }
}
