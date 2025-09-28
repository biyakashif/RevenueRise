<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ContactSetting;

class ContactSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ContactSetting::create([
            'show_email' => true,
            'email' => 'support@taskapp.com',
            'show_whatsapp' => true,
            'whatsapp' => '+352 621 123 456',
            'show_telegram' => true,
            'telegram' => '@taskapp_support',
            'show_office' => true,
            'office_address' => "TaskApp Luxembourg S.A.\n12 Avenue de la Liberté\nL-1930 Luxembourg\nLuxembourg",
        ]);
    }
}
