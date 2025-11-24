<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'price' => (float) $this->price,
            'compare_price' => $this->compare_price ? (float) $this->compare_price : null,
            'discount_percentage' => $this->compare_price
                ? round((($this->compare_price - $this->price) / $this->compare_price) * 100)
                : null,
            'stock_quantity' => $this->stock_quantity,
            'is_in_stock' => $this->isInStock(),
            'is_low_stock' => $this->isLowStock(),
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'main_image' => $this->main_image,
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'meta_data' => $this->meta_data,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
