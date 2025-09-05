<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Task;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if users already exist, if not create demo users
        if (User::count() == 0) {
            // Create demo user
            $user = User::create([
                'name' => 'Demo User',
                'mobile_number' => '1234567890',
                'password' => Hash::make('password'),
                'balance' => 1000.00,
                'vip_level' => 'VIP1',
                'invitation_code' => 'DEMO123',
            ]);

            // Create admin user
            $admin = User::create([
                'name' => 'Admin User',
                'mobile_number' => '0987654321',
                'password' => Hash::make('admin123'),
                'balance' => 5000.00,
                'vip_level' => 'VIP3',
                'invitation_code' => 'ADMIN123',
                'role' => 'admin',
            ]);
        }

        // Only create products if they don't exist
        if (Product::count() == 0) {
            // Create sample products
            $products = [
                [
                    'product_id' => 'PROD001',
                    'title' => 'iPhone 15 Pro',
                    'description' => 'Latest iPhone with advanced features',
                    'purchase_price' => 999.00,
                    'selling_price' => 1099.00,
                    'commission_reward' => 50.00,
                    'commission_percentage' => 5.0,
                    'image_path' => '/images/iphone15.jpg',
                    'type' => 'VIP1',
                ],
                [
                    'product_id' => 'PROD002',
                    'title' => 'Samsung Galaxy S24',
                    'description' => 'Premium Android smartphone',
                    'purchase_price' => 899.00,
                    'selling_price' => 999.00,
                    'commission_reward' => 45.00,
                    'commission_percentage' => 4.5,
                    'image_path' => '/images/samsung-s24.jpg',
                    'type' => 'VIPs',
                ],
                [
                    'product_id' => 'LUCKY001',
                    'title' => 'MacBook Pro M3',
                    'description' => 'High-performance laptop for professionals',
                    'purchase_price' => 1999.00,
                    'selling_price' => 2199.00,
                    'commission_reward' => 200.00,
                    'commission_percentage' => 10.0,
                    'image_path' => '/images/macbook-pro.jpg',
                    'type' => 'Lucky Order',
                ],
                [
                    'product_id' => 'PROD003',
                    'title' => 'iPad Pro',
                    'description' => 'Professional tablet for creative work',
                    'purchase_price' => 799.00,
                    'selling_price' => 899.00,
                    'commission_reward' => 40.00,
                    'commission_percentage' => 4.5,
                    'image_path' => '/images/ipad-pro.jpg',
                    'type' => 'VIP1',
                ],
                [
                    'product_id' => 'PROD004',
                    'title' => 'Apple Watch Ultra',
                    'description' => 'Advanced smartwatch for active lifestyles',
                    'purchase_price' => 599.00,
                    'selling_price' => 699.00,
                    'commission_reward' => 35.00,
                    'commission_percentage' => 5.0,
                    'image_path' => '/images/apple-watch.jpg',
                    'type' => 'VIP1',
                ],
            ];

            foreach ($products as $productData) {
                $product = Product::create($productData);
                
                // Create tasks for VIP1 level
                Task::create([
                    'user_id' => null, // Global tasks
                    'name' => 'VIP1',
                    'product_id' => $product->id,
                    'product_type' => $product->type,
                    'position' => $product->id,
                ]);
            }
        }
    }
}
