<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['customer.user', 'items.product']);

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Date range filter
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['customer.user', 'items.product']);

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|array',
            'shipping_address.recipient_name' => 'required|string',
            'shipping_address.phone' => 'required|string',
            'shipping_address.street_address' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.postal_code' => 'required|string',
            'shipping_address.country' => 'required|string',
            'payment_method' => 'required|in:cod,bank_transfer,momo,vnpay,credit_card',
            'notes' => 'nullable|string',
            'shipping_fee' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'shipping_address' => $validated['shipping_address'],
                'payment_method' => $validated['payment_method'],
                'shipping_fee' => $validated['shipping_fee'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'subtotal' => 0,
                'total' => 0,
            ]);

            $subtotal = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check stock
                if ($product->track_inventory && $product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                $itemSubtotal = $product->price * $item['quantity'];
                $subtotal += $itemSubtotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $itemSubtotal,
                ]);

                // Update stock
                if ($product->track_inventory) {
                    $product->decrement('stock_quantity', $item['quantity']);
                }
            }

            $order->update([
                'subtotal' => $subtotal,
                'total' => $subtotal + $order->shipping_fee,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully',
                'data' => new OrderResource($order->load(['items.product'])),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
        ]);

        $order->update(['status' => $validated['status']]);

        if ($validated['status'] === 'shipped') {
            $order->update(['shipped_at' => now()]);
        } elseif ($validated['status'] === 'delivered') {
            $order->update(['delivered_at' => now()]);
        }

        return response()->json([
            'message' => 'Order status updated successfully',
            'data' => new OrderResource($order),
        ]);
    }

    public function cancel(Order $order): JsonResponse
    {
        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json([
                'message' => 'Cannot cancel order with current status',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Restore stock
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product && $product->track_inventory) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }

            $order->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json([
                'message' => 'Order cancelled successfully',
                'data' => new OrderResource($order),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to cancel order',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
