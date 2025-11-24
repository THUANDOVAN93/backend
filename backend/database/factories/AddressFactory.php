<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => $this->faker->randomElement(['home', 'office', 'other']),
            'recipient_name' => $this->faker->name(),
            'phone' => $this->faker->numerify('09########'),
            'street_address' => $this->faker->streetAddress(),
            'city' => $this->faker->randomElement([
                'Ho Chi Minh City',
                'Hanoi',
                'Da Nang',
                'Can Tho',
                'Hai Phong',
                'Bien Hoa',
                'Nha Trang',
                'Hue',
                'Vung Tau'
            ]),
            'state' => $this->faker->optional()->randomElement([
                'Ho Chi Minh',
                'Hanoi',
                'Da Nang',
                'Can Tho',
                'Hai Phong',
                'Dong Nai',
                'Khanh Hoa',
                'Thua Thien Hue',
                'Ba Ria-Vung Tau'
            ]),
            'postal_code' => $this->faker->numerify('######'),
            'country' => 'Vietnam',
            'is_default' => false,
        ];
    }

    /**
     * Indicate that this is the default address.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that this is a home address.
     */
    public function home(): static
    {
        return $this->state(fn (array $attributes) => [
            'label' => 'home',
        ]);
    }

    /**
     * Indicate that this is an office address.
     */
    public function office(): static
    {
        return $this->state(fn (array $attributes) => [
            'label' => 'office',
        ]);
    }
}
