<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('user_orders', 'initial_balance')) {
                $table->decimal('initial_balance', 12, 2)->nullable()->after('order_number');
            }
            if (!Schema::hasColumn('user_orders', 'purchase_price')) {
                $table->decimal('purchase_price', 12, 2)->nullable()->after('initial_balance');
            }
            if (!Schema::hasColumn('user_orders', 'required_balance_to_confirm')) {
                $table->decimal('required_balance_to_confirm', 12, 2)->nullable()->after('purchase_price');
            }
            if (!Schema::hasColumn('user_orders', 'required_deposit_amount')) {
                $table->decimal('required_deposit_amount', 12, 2)->nullable()->after('required_balance_to_confirm');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_orders', function (Blueprint $table) {
            if (Schema::hasColumn('user_orders', 'required_deposit_amount')) {
                $table->dropColumn('required_deposit_amount');
            }
            if (Schema::hasColumn('user_orders', 'required_balance_to_confirm')) {
                $table->dropColumn('required_balance_to_confirm');
            }
            if (Schema::hasColumn('user_orders', 'purchase_price')) {
                $table->dropColumn('purchase_price');
            }
            if (Schema::hasColumn('user_orders', 'initial_balance')) {
                $table->dropColumn('initial_balance');
            }
        });
    }
};
