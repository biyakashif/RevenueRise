<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['mobile_number' => '0987654321'],
            [
                'name' => 'Test User',
                'password' => 'password', // stored as-is per model mutator
                'withdraw_password' => null,
                'invitation_code' => 'USER001',
                'balance' => 0.0,
                'role' => 'user',
                'referred_by' => null,
                'vip_level' => 'VIP1',
                'avatar_url' => null,
                'todays_profit' => 0.0,
                'last_profit_reset' => now()->toDateString(),
                'force_lucky_order' => false,
                'withdraw_limit' => 30.00,
            ]
        );
    }
}
