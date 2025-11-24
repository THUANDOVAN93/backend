<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\Address;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding customers...');

        // Create 30 customers with users and addresses
        for ($i = 1; $i <= 30; $i++) {
            // Create user
            $user = User::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]);

            // Create customer
            $customer = Customer::create([
                'user_id' => $user->id,
                'phone' => fake()->numerify('09########'),
                'date_of_birth' => fake()->date('Y-m-d', '-18 years'),
                'gender' => fake()->randomElement(['male', 'female', 'other']),
                'notes' => fake()->optional(0.2)->sentence(),
            ]);

            // Create 1-3 addresses per customer
            $addressCount = rand(1, 3);
            for ($j = 0; $j < $addressCount; $j++) {
                Address::create([
                    'customer_id' => $customer->id,
                    'label' => fake()->randomElement(['home', 'office', 'other']),
                    'recipient_name' => $j === 0 ? $user->name : fake()->name(),
                    'phone' => fake()->numerify('09########'),
                    'street_address' => fake()->streetAddress(),
                    'city' => fake()->randomElement([
                        'Ho Chi Minh City',
                        'Hanoi',
                        'Da Nang',
                        'Can Tho',
                        'Hai Phong',
                        'Bien Hoa',
                        'Nha Trang',
                    ]),
                    'state' => fake()->optional()->randomElement([
                        'Ho Chi Minh',
                        'Hanoi',
                        'Da Nang',
                        'Can Tho',
                    ]),
                    'postal_code' => fake()->numerify('######'),
                    'country' => 'Vietnam',
                    'is_default' => $j === 0, // First address is default
                ]);
            }

            $this->command->info("Created customer #{$i}: {$user->name} with {$addressCount} address(es)");
        }

        $this->command->info('Customers seeded successfully!');
    }
}
