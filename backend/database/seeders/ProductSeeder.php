<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding products...');

        // Sample products for different categories
        $productsByCategory = [
            'Smartphones' => [
                ['name' => 'iPhone 15 Pro Max', 'price' => 29990000, 'compare_price' => 32990000],
                ['name' => 'Samsung Galaxy S24 Ultra', 'price' => 27990000, 'compare_price' => 29990000],
                ['name' => 'Google Pixel 8 Pro', 'price' => 24990000, 'compare_price' => null],
                ['name' => 'Xiaomi 14 Pro', 'price' => 19990000, 'compare_price' => 22990000],
                ['name' => 'OnePlus 12', 'price' => 18990000, 'compare_price' => null],
            ],
            'Laptops' => [
                ['name' => 'MacBook Pro 16" M3', 'price' => 59990000, 'compare_price' => 64990000],
                ['name' => 'Dell XPS 15', 'price' => 45990000, 'compare_price' => null],
                ['name' => 'HP Spectre x360', 'price' => 42990000, 'compare_price' => 46990000],
                ['name' => 'Lenovo ThinkPad X1 Carbon', 'price' => 48990000, 'compare_price' => null],
                ['name' => 'ASUS ROG Zephyrus G14', 'price' => 38990000, 'compare_price' => 42990000],
            ],
            'Headphones' => [
                ['name' => 'Sony WH-1000XM5', 'price' => 8990000, 'compare_price' => 9990000],
                ['name' => 'AirPods Pro 2', 'price' => 6490000, 'compare_price' => null],
                ['name' => 'Bose QuietComfort Ultra', 'price' => 9990000, 'compare_price' => 10990000],
                ['name' => 'Sennheiser Momentum 4', 'price' => 7990000, 'compare_price' => null],
            ],
            'Men\'s Clothing' => [
                ['name' => 'Classic Polo Shirt', 'price' => 299000, 'compare_price' => 399000],
                ['name' => 'Slim Fit Jeans', 'price' => 599000, 'compare_price' => null],
                ['name' => 'Oxford Dress Shirt', 'price' => 449000, 'compare_price' => 549000],
                ['name' => 'Casual Jacket', 'price' => 899000, 'compare_price' => 1199000],
            ],
            'Women\'s Clothing' => [
                ['name' => 'Summer Dress', 'price' => 499000, 'compare_price' => 699000],
                ['name' => 'Elegant Blouse', 'price' => 399000, 'compare_price' => null],
                ['name' => 'High-Waist Trousers', 'price' => 549000, 'compare_price' => 699000],
                ['name' => 'Cardigan Sweater', 'price' => 459000, 'compare_price' => null],
            ],
            'Furniture' => [
                ['name' => 'Modern Sofa 3-Seater', 'price' => 12990000, 'compare_price' => 14990000],
                ['name' => 'Dining Table Set', 'price' => 8990000, 'compare_price' => null],
                ['name' => 'Office Chair Ergonomic', 'price' => 3990000, 'compare_price' => 4990000],
                ['name' => 'King Size Bed Frame', 'price' => 9990000, 'compare_price' => null],
            ],
            'Kitchen Appliances' => [
                ['name' => 'Air Fryer 5L', 'price' => 2490000, 'compare_price' => 2990000],
                ['name' => 'Coffee Machine', 'price' => 4990000, 'compare_price' => null],
                ['name' => 'Blender Pro', 'price' => 1990000, 'compare_price' => 2490000],
                ['name' => 'Microwave Oven', 'price' => 3490000, 'compare_price' => null],
            ],
            'Skincare' => [
                ['name' => 'Hydrating Face Cream', 'price' => 599000, 'compare_price' => 799000],
                ['name' => 'Vitamin C Serum', 'price' => 749000, 'compare_price' => null],
                ['name' => 'Gentle Cleanser', 'price' => 399000, 'compare_price' => 499000],
                ['name' => 'SPF 50 Sunscreen', 'price' => 449000, 'compare_price' => null],
            ],
            'Fitness Equipment' => [
                ['name' => 'Adjustable Dumbbells Set', 'price' => 3990000, 'compare_price' => 4990000],
                ['name' => 'Yoga Mat Premium', 'price' => 599000, 'compare_price' => null],
                ['name' => 'Resistance Bands Set', 'price' => 399000, 'compare_price' => 499000],
                ['name' => 'Treadmill Foldable', 'price' => 8990000, 'compare_price' => 9990000],
            ],
        ];

        $totalProducts = 0;

        foreach ($productsByCategory as $categoryName => $products) {
            $category = Category::where('name', $categoryName)->first();

            if (!$category) {
                $this->command->warn("Category '{$categoryName}' not found. Skipping...");
                continue;
            }

            foreach ($products as $productData) {
                $slug = Str::slug($productData['name']) . '-' . rand(100, 999);

                $product = Product::create([
                    'name' => $productData['name'],
                    'slug' => $slug,
                    'sku' => strtoupper(Str::random(3)) . '-' . rand(10000, 99999),
                    'description' => $this->generateDescription($productData['name']),
                    'short_description' => $this->generateShortDescription($productData['name']),
                    'price' => $productData['price'],
                    'compare_price' => $productData['compare_price'],
                    'cost' => $productData['price'] * 0.6,
                    'stock_quantity' => rand(10, 200),
                    'low_stock_threshold' => 10,
                    'track_inventory' => true,
                    'is_active' => true,
                    'is_featured' => rand(0, 100) > 80, // 20% chance of being featured
                    'main_image' => 'products/default.jpg',
                    'meta_data' => [
                        'brand' => $this->getBrandForCategory($categoryName),
                        'warranty' => rand(6, 24) . ' months',
                        'condition' => 'New',
                    ],
                ]);

                // Attach to category
                $product->categories()->attach($category->id);

                // Create 2-4 additional images
                $imageCount = rand(2, 4);
                for ($i = 0; $i < $imageCount; $i++) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => "products/{$slug}-{$i}.jpg",
                        'sort_order' => $i,
                    ]);
                }

                $totalProducts++;
            }

            $productCount = count($products);

            $this->command->info("Seeded {$productCount} products for category: {$categoryName}");
        }

        // Create some additional random products
        $randomCount = 50;
        $categories = Category::whereNotNull('parent_id')->get();

        for ($i = 0; $i < $randomCount; $i++) {
            $product = Product::factory()->create();

            // Attach to 1-3 random categories
            $randomCategories = $categories->random(rand(1, 3));
            $product->categories()->attach($randomCategories->pluck('id'));

            // Create 2-5 images
            ProductImage::factory()
                ->count(rand(2, 5))
                ->create(['product_id' => $product->id]);

            $totalProducts++;
        }

        $this->command->info("Created {$randomCount} additional random products");
        $this->command->info("Total products seeded: {$totalProducts}");
    }

    private function generateDescription(string $name): string
    {
        return "Experience the best with {$name}. This premium product combines quality, functionality, and style. "
            . "Designed with attention to detail and built to last. Perfect for both everyday use and special occasions. "
            . "Features advanced technology and user-friendly design. Backed by manufacturer warranty and customer support.";
    }

    private function generateShortDescription(string $name): string
    {
        return "Premium {$name} with excellent quality and design. Perfect for your needs with great value for money.";
    }

    private function getBrandForCategory(string $category): string
    {
        $brands = [
            'Smartphones' => ['Apple', 'Samsung', 'Google', 'Xiaomi', 'OnePlus'],
            'Laptops' => ['Apple', 'Dell', 'HP', 'Lenovo', 'ASUS'],
            'Headphones' => ['Sony', 'Apple', 'Bose', 'Sennheiser', 'JBL'],
            'Men\'s Clothing' => ['Ralph Lauren', 'H&M', 'Zara', 'Uniqlo', 'Nike'],
            'Women\'s Clothing' => ['Zara', 'H&M', 'Forever 21', 'Mango', 'Uniqlo'],
            'Furniture' => ['IKEA', 'Ashley', 'Wayfair', 'West Elm', 'CB2'],
            'Kitchen Appliances' => ['KitchenAid', 'Cuisinart', 'Ninja', 'Breville', 'Hamilton Beach'],
            'Skincare' => ['CeraVe', 'La Roche-Posay', 'Neutrogena', 'The Ordinary', 'Cetaphil'],
            'Fitness Equipment' => ['Bowflex', 'NordicTrack', 'ProForm', 'Schwinn', 'Reebok'],
        ];

        $categoryBrands = $brands[$category] ?? ['Generic Brand', 'Premium', 'Quality', 'Elite'];
        return $categoryBrands[array_rand($categoryBrands)];
    }
}
