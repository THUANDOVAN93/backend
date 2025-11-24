<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'description',
        'short_description',
        'price',
        'compare_price',
        'cost',
        'stock_quantity',
        'low_stock_threshold',
        'track_inventory',
        'is_active',
        'is_featured',
        'main_image',
        'meta_data',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'track_inventory' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'meta_data' => 'array',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isInStock(): bool
    {
        if (!$this->track_inventory) {
            return true;
        }
        return $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        if (!$this->track_inventory) {
            return false;
        }
        return $this->stock_quantity <= $this->low_stock_threshold;
    }
}
