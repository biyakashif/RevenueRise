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
        $table->dropColumn('task_name');
        $table->dropColumn('status');
        $table->dropColumn('order_data');
    });
}
};
