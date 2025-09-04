<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create an admin user
        User::create([
            'name' => 'Admin User',
            'mobile_number' => '1234567890',
            'invitation_code' => 'ADMIN001',
            'balance' => 1000.00,
            'password' => 'password', // Plain text password
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Remove this line if you don't want fake users
        // User::factory()->count(24)->create();
    }
}