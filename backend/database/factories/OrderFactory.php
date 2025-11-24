<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100000, 5000000);
        $tax = $subtotal * 0.10;
        $shippingFee = $this->faker->randomFloat(2, 20000, 50000);
        $discount = $this->faker->optional(0.3)->randomFloat(2, 10000, 100000) ?? 0;
        $total = $subtotal + $tax + $shippingFee - $discount;

        $status = $this->faker->randomElement([
            'pending',
            'processing',
            'shipped',
            'delivered',
            'cancelled',
        ]);

        $paymentStatus = match($status) {
            'delivered' => 'paid',
            'cancelled' => $this->faker->randomElement(['pending', 'refunded']),
            'shipped', 'processing' => $this->faker->randomElement(['pending', 'paid']),
            default => 'pending',
        };

        return [
            'order_number' => 'ORD-' . strtoupper($this->faker->unique()->bothify('??######')),
            'customer_id' => Customer::factory(),
            'status' => $status,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_fee' => $shippingFee,
            'discount' => $discount,
            'total' => $total,
            'currency' => 'VND',
            'payment_method' => $this->faker->randomElement([
                'cod',
                'bank_transfer',
                'momo',
                'vnpay',
                'credit_card'
            ]),
            'payment_status' => $paymentStatus,
            'notes' => $this->faker->optional(0.3)->sentence(),
            'shipping_address' => [
                'recipient_name' => $this->faker->name(),
                'phone' => $this->faker->numerify('09########'),
                'street_address' => $this->faker->streetAddress(),
                'city' => $this->faker->randomElement([
                    'Ho Chi Minh City',
                    'Hanoi',
                    'Da Nang',
                    'Can Tho',
                ]),
                'postal_code' => $this->faker->numerify('######'),
                'country' => 'Vietnam',
            ],
            'paid_at' => $paymentStatus === 'paid' ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'shipped_at' => in_array($status, ['shipped', 'delivered']) ? $this->faker->dateTimeBetween('-20 days', 'now') : null,
            'delivered_at' => $status === 'delivered' ? $this->faker->dateTimeBetween('-10 days', 'now') : null,
        ];
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
            'paid_at' => null,
            'shipped_at' => null,
            'delivered_at' => null,
        ]);
    }

    /**
     * Indicate that the order is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'payment_status' => 'paid',
            'paid_at' => $this->faker->dateTimeBetween('-5 days', 'now'),
            'shipped_at' => null,
            'delivered_at' => null,
        ]);
    }

    /**
     * Indicate that the order is shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shipped',
            'payment_status' => 'paid',
            'paid_at' => $this->faker->dateTimeBetween('-10 days', '-5 days'),
            'shipped_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
            'delivered_at' => null,
        ]);
    }

    /**
     * Indicate that the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'paid_at' => $this->faker->dateTimeBetween('-20 days', '-10 days'),
            'shipped_at' => $this->faker->dateTimeBetween('-15 days', '-7 days'),
            'delivered_at' => $this->faker->dateTimeBetween('-5 days', 'now'),
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'payment_status' => 'refunded',
        ]);
    }

    /**
     * Indicate cash on delivery payment method.
     */
    public function cod(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cod',
        ]);
    }
}
