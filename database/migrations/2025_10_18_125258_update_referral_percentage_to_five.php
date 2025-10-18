<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('referral_percentage', 10.00)
            ->update(['referral_percentage' => 5.00]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('referral_percentage', 5.00)
            ->update(['referral_percentage' => 10.00]);
    }
};
