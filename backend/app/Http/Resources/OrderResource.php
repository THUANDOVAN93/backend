<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'shipping_fee' => (float) $this->shipping_fee,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'notes' => $this->notes,
            'shipping_address' => $this->shipping_address,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->when(isset($this->items_count), $this->items_count),
            'paid_at' => $this->paid_at?->toISOString(),
            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
