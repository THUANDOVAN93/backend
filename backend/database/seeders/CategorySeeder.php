<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Main categories with their subcategories
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'All electronic devices and accessories',
                'children' => [
                    'Smartphones',
                    'Laptops',
                    'Tablets',
                    'Headphones',
                    'Cameras',
                    'Smart Watches',
                    'Gaming Consoles',
                ]
            ],
            [
                'name' => 'Fashion',
                'description' => 'Clothing and fashion accessories',
                'children' => [
                    'Men\'s Clothing',
                    'Women\'s Clothing',
                    'Kids\' Clothing',
                    'Shoes',
                    'Bags',
                    'Watches',
                    'Jewelry',
                    'Sunglasses',
                ]
            ],
            [
                'name' => 'Home & Living',
                'description' => 'Home appliances and living essentials',
                'children' => [
                    'Furniture',
                    'Kitchen Appliances',
                    'Home Decor',
                    'Bedding',
                    'Lighting',
                    'Storage',
                ]
            ],
            [
                'name' => 'Beauty & Personal Care',
                'description' => 'Beauty products and personal care items',
                'children' => [
                    'Skincare',
                    'Makeup',
                    'Haircare',
                    'Fragrances',
                    'Personal Care',
                ]
            ],
            [
                'name' => 'Sports & Outdoors',
                'description' => 'Sports equipment and outdoor gear',
                'children' => [
                    'Fitness Equipment',
                    'Sports Wear',
                    'Camping & Hiking',
                    'Cycling',
                    'Swimming',
                ]
            ],
            [
                'name' => 'Books & Media',
                'description' => 'Books, music, and media products',
                'children' => [
                    'Books',
                    'E-books',
                    'Music',
                    'Movies & TV',
                    'Video Games',
                ]
            ],
            [
                'name' => 'Toys & Kids',
                'description' => 'Toys and products for children',
                'children' => [
                    'Action Figures',
                    'Dolls',
                    'Educational Toys',
                    'Baby Products',
                    'Board Games',
                ]
            ],
            [
                'name' => 'Food & Beverages',
                'description' => 'Food items and beverages',
                'children' => [
                    'Snacks',
                    'Beverages',
                    'Organic Food',
                    'Gourmet',
                    'Health Food',
                ]
            ],
            [
                'name' => 'Automotive',
                'description' => 'Car accessories and automotive products',
                'children' => [
                    'Car Electronics',
                    'Car Care',
                    'Accessories',
                    'Parts',
                ]
            ],
            [
                'name' => 'Pet Supplies',
                'description' => 'Products for your pets',
                'children' => [
                    'Pet Food',
                    'Pet Toys',
                    'Pet Grooming',
                    'Pet Health',
                ]
            ],
        ];

        $sortOrder = 0;

        foreach ($categories as $categoryData) {
            // Create parent category
            $parent = Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'description' => $categoryData['description'],
                'is_active' => true,
                'sort_order' => $sortOrder++,
            ]);

            $this->command->info("Created parent category: {$parent->name}");

            // Create child categories
            $childSortOrder = 0;
            foreach ($categoryData['children'] as $childName) {
                $child = Category::create([
                    'name' => $childName,
                    'slug' => Str::slug($childName),
                    'description' => "Browse our collection of {$childName}",
                    'parent_id' => $parent->id,
                    'is_active' => true,
                    'sort_order' => $childSortOrder++,
                ]);

                $this->command->info("  ├─ Created child category: {$child->name}");
            }
        }

        $this->command->info('Categories seeded successfully!');
    }
}
