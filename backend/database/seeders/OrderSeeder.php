<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding orders...');

        $customers = Customer::with('defaultAddress')->get();
        $products = Product::where('is_active', true)->get();

        if ($customers->isEmpty()) {
            $this->command->error('No customers found. Please run CustomerSeeder first.');
            return;
        }

        if ($products->isEmpty()) {
            $this->command->error('No products found. Please run ProductSeeder first.');
            return;
        }

        // Create 100 orders
        for ($i = 1; $i <= 100; $i++) {
            $customer = $customers->random();
            $defaultAddress = $customer->defaultAddress;

            // Prepare shipping address
            $shippingAddress = [
                'recipient_name' => $defaultAddress ? $defaultAddress->recipient_name : $customer->user->name,
                'phone' => $defaultAddress ? $defaultAddress->phone : $customer->phone,
                'street_address' => $defaultAddress ? $defaultAddress->street_address : fake()->streetAddress(),
                'city' => $defaultAddress ? $defaultAddress->city : 'Ho Chi Minh City',
                'state' => $defaultAddress ? $defaultAddress->state : 'Ho Chi Minh',
                'postal_code' => $defaultAddress ? $defaultAddress->postal_code : fake()->numerify('######'),
                'country' => 'Vietnam',
            ];

            // Determine order status and dates
            $statusOptions = [
                ['status' => 'pending', 'weight' => 15],
                ['status' => 'processing', 'weight' => 20],
                ['status' => 'shipped', 'weight' => 25],
                ['status' => 'delivered', 'weight' => 35],
                ['status' => 'cancelled', 'weight' => 5],
            ];

            $status = $this->weightedRandom($statusOptions);

            $paymentStatus = match($status) {
                'delivered' => 'paid',
                'cancelled' => fake()->randomElement(['pending', 'refunded']),
                'shipped', 'processing' => fake()->randomElement(['pending', 'paid']),
                default => 'pending',
            };

            // Create order
            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(fake()->unique()->bothify('??######')),
                'customer_id' => $customer->id,
                'status' => $status,
                'subtotal' => 0, // Will be calculated
                'tax' => 0,
                'shipping_fee' => fake()->randomElement([20000, 30000, 50000]),
                'discount' => fake()->optional(0.3)->randomElement([50000, 100000, 200000]) ?? 0,
                'total' => 0, // Will be calculated
                'currency' => 'VND',
                'payment_method' => fake()->randomElement(['cod', 'bank_transfer', 'momo', 'vnpay']),
                'payment_status' => $paymentStatus,
                'notes' => fake()->optional(0.2)->sentence(),
                'shipping_address' => $shippingAddress,
                'paid_at' => $paymentStatus === 'paid' ? fake()->dateTimeBetween('-60 days', '-1 day') : null,
                'shipped_at' => in_array($status, ['shipped', 'delivered']) ? fake()->dateTimeBetween('-30 days', '-1 day') : null,
                'delivered_at' => $status === 'delivered' ? fake()->dateTimeBetween('-20 days', 'now') : null,
                'created_at' => fake()->dateTimeBetween('-90 days', 'now'),
            ]);

            // Add 1-5 items to the order
            $itemCount = rand(1, 5);
            $subtotal = 0;

            $selectedProducts = $products->random($itemCount);

            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 3);
                $price = $product->price;
                $itemSubtotal = $price * $quantity;
                $subtotal += $itemSubtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $itemSubtotal,
                ]);
            }

            // Calculate totals
            $tax = $subtotal * 0.10; // 10% tax
            $total = $subtotal + $tax + $order->shipping_fee - $order->discount;

            $order->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            $this->command->info("Created order #{$i}: {$order->order_number} with {$itemCount} item(s) - Status: {$status}");
        }

        $this->command->info('Orders seeded successfully!');
    }

    /**
     * Weighted random selection
     */
    private function weightedRandom(array $options): string
    {
        $totalWeight = array_sum(array_column($options, 'weight'));
        $random = rand(1, $totalWeight);

        $currentWeight = 0;
        foreach ($options as $option) {
            $currentWeight += $option['weight'];
            if ($random <= $currentWeight) {
                return $option['status'];
            }
        }

        return $options[0]['status'];
    }
}
