<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_orders', function (Blueprint $table) {
            // Drop the foreign key first
            $table->dropForeign(['user_id']);

            // Then drop the index
            $table->dropIndex('user_orders_user_id_order_number_index');
        });
    }

    public function down(): void
    {
        Schema::table('user_orders', function (Blueprint $table) {
            // Re-add index
            $table->index(['user_id', 'order_number']);

            // Re-add foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
