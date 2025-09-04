<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add product_id only if it doesn't exist
            if (!Schema::hasColumn('products', 'product_id')) {
                $table->string('product_id')->unique()->after('id');
            }

            // Rename price to selling_price if price exists and selling_price doesn't
            if (Schema::hasColumn('products', 'price') && !Schema::hasColumn('products', 'selling_price')) {
                $table->renameColumn('price', 'selling_price');
            }

            // Rename profit_multiplier to commission_percentage if profit_multiplier exists
            if (Schema::hasColumn('products', 'profit_multiplier') && !Schema::hasColumn('products', 'commission_percentage')) {
                $table->renameColumn('profit_multiplier', 'commission_percentage');
            }

            // Add purchase_price if it doesn't exist
            if (!Schema::hasColumn('products', 'purchase_price')) {
                $table->decimal('purchase_price', 10, 2)->after('description');
            }

            // Add commission_reward if it doesn't exist
            if (!Schema::hasColumn('products', 'commission_reward')) {
                $table->decimal('commission_reward', 10, 2)->after('selling_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop product_id if it exists
            if (Schema::hasColumn('products', 'product_id')) {
                $table->dropColumn('product_id');
            }

            // Rename selling_price back to price if selling_price exists
            if (Schema::hasColumn('products', 'selling_price') && !Schema::hasColumn('products', 'price')) {
                $table->renameColumn('selling_price', 'price');
            }

            // Rename commission_percentage back to profit_multiplier if commission_percentage exists
            if (Schema::hasColumn('products', 'commission_percentage') && !Schema::hasColumn('products', 'profit_multiplier')) {
                $table->renameColumn('commission_percentage', 'profit_multiplier');
            }

            // Drop purchase_price if it exists
            if (Schema::hasColumn('products', 'purchase_price')) {
                $table->dropColumn('purchase_price');
            }

            // Drop commission_reward if it exists
            if (Schema::hasColumn('products', 'commission_reward')) {
                $table->dropColumn('commission_reward');
            }
        });
    }
};