<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);
        $price = $this->faker->randomFloat(2, 10000, 5000000);
        $hasComparePrice = $this->faker->boolean(40);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numerify('###'),
            'sku' => strtoupper($this->faker->bothify('SKU-????-####')),
            'description' => $this->faker->paragraph(5),
            'short_description' => $this->faker->sentence(15),
            'price' => $price,
            'compare_price' => $hasComparePrice ? $price * $this->faker->randomFloat(2, 1.2, 2.0) : null,
            'cost' => $price * $this->faker->randomFloat(2, 0.4, 0.7),
            'stock_quantity' => $this->faker->numberBetween(0, 500),
            'low_stock_threshold' => 10,
            'track_inventory' => $this->faker->boolean(90),
            'is_active' => $this->faker->boolean(90),
            'is_featured' => $this->faker->boolean(20),
            'main_image' => $this->faker->imageUrl(640, 480, 'products'),
            'meta_data' => [
                'brand' => $this->faker->company(),
                'weight' => $this->faker->numberBetween(100, 5000),
                'dimensions' => [
                    'length' => $this->faker->numberBetween(10, 100),
                    'width' => $this->faker->numberBetween(10, 100),
                    'height' => $this->faker->numberBetween(5, 50),
                ],
            ],
        ];
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the product has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * Indicate that the product is on sale.
     */
    public function onSale(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'];
            return [
                'compare_price' => $price * $this->faker->randomFloat(2, 1.3, 2.5),
            ];
        });
    }
}
