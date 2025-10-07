<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('crypto_deposit_details', function (Blueprint $table) {
            $table->string('currency')->default('USDT');
            $table->string('network')->default('TRC20');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crypto_deposit_details', function (Blueprint $table) {
            $table->dropColumn(['currency', 'network']);
        });
    }
};
