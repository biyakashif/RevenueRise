<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'mobile_number' => fake()->unique()->phoneNumber(), // Replace email with mobile_number
            'invitation_code' => 'INV' . Str::random(5), // Generate unique invitation code
            'balance' => fake()->randomFloat(2, 0, 1000),
            'password' => '$2y$12$cupqf/Jx0uRbpD/m5oHNxeYgA4zSkE7pU9x3rjXgLHe5WFCLInWYO', // password
            'role' => 'user',
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the user is an admin.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'admin',
                'mobile_number' => '1234567890', // Admin mobile number
                'invitation_code' => 'ADMIN001',
                'balance' => 1000.00,
            ];
        });
    }
}