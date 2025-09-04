<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add new flat columns if missing
        Schema::table('user_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('user_orders', 'user_name')) {
                $table->string('user_name')->after('user_id');
            }
            if (!Schema::hasColumn('user_orders', 'mobile_number')) {
                $table->string('mobile_number')->after('user_name');
            }
            if (!Schema::hasColumn('user_orders', 'vip_level')) {
                $table->string('vip_level')->after('mobile_number');
            }
            if (!Schema::hasColumn('user_orders', 'order_number')) {
                $table->unsignedInteger('order_number')->after('status');
                $table->index(['user_id', 'order_number']);
            }
        });

        // Drop JSON payload column you no longer want
        if (Schema::hasColumn('user_orders', 'order_data')) {
            Schema::table('user_orders', function (Blueprint $table) {
                $table->dropColumn('order_data');
            });
        }
    }

    public function down(): void
    {
        // Recreate the dropped column (nullable) and remove new columns
        Schema::table('user_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('user_orders', 'order_data')) {
                $table->json('order_data')->nullable();
            }

            if (Schema::hasColumn('user_orders', 'order_number')) {
                $table->dropIndex(['user_id', 'order_number']);
                $table->dropColumn('order_number');
            }
            if (Schema::hasColumn('user_orders', 'vip_level')) {
                $table->dropColumn('vip_level');
            }
            if (Schema::hasColumn('user_orders', 'mobile_number')) {
                $table->dropColumn('mobile_number');
            }
            if (Schema::hasColumn('user_orders', 'user_name')) {
                $table->dropColumn('user_name');
            }
        });
    }
};
