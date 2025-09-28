<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AutoReplyMessage;
use App\Models\AutoReplySetting;

class AutoReplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create auto reply setting
        AutoReplySetting::create([
            'is_enabled' => false
        ]);

        // Create default auto reply messages
        AutoReplyMessage::create([
            'message' => 'Welcome! How can we help you today?',
            'order' => 1,
            'is_active' => true
        ]);

        AutoReplyMessage::create([
            'message' => 'Hello [user_name]! Thank you for contacting us.',
            'order' => 2,
            'is_active' => true
        ]);

        AutoReplyMessage::create([
            'message' => "You can also contact us through:\n\n📧 Email: support@taskapp.com\n📱 WhatsApp: +352 621 123 456\n📞 Telegram: @taskapp_support",
            'order' => 3,
            'is_active' => true
        ]);
    }
}
