<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('user_orders', function (Blueprint $table) {
        $table->string('task_name')->after('product_id')->nullable();
        $table->string('status')->default('pending')->after('task_name');
        $table->json('order_data')->after('status')->nullable();
    });
}

public function down()
{
    Schema::table('user_orders', function (Blueprint $table) {
        // Drop columns only if they exist
        if (Schema::hasColumn('user_orders', 'task_name')) {
            $table->dropColumn('task_name');
        }
        if (Schema::hasColumn('user_orders', 'status')) {
            $table->dropColumn('status');
        }
        if (Schema::hasColumn('user_orders', 'order_data')) {
            $table->dropColumn('order_data');
        }
    });
}
};
