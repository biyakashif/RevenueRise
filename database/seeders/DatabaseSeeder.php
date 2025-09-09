<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Keep admin seeding
        User::updateOrCreate(
            ['mobile_number' => '1234567890'],
            [
                'name' => 'Admin User',
                'invitation_code' => 'ADMIN001',
                'balance' => 1000.00,
                'password' => 'password', // stored as-is per model mutator
                'role' => 'admin',
                'referred_by' => null,
                'vip_level' => 'VIP1',
                'avatar_url' => null,
                'todays_profit' => 0.00,
                'last_profit_reset' => now()->toDateString(),
                'force_lucky_order' => false,
                'withdraw_limit' => 30.00,
                'withdraw_password' => null,
            ]
        );

        // Seed a regular user via dedicated seeder
        $this->call(UserSeeder::class);
    }
}