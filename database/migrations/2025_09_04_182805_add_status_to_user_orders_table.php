<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('user_orders', 'status')) {
                $table->string('status')->default('pending')->after('id'); 
                // Add after 'id' or any column you prefer
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_orders', function (Blueprint $table) {
            if (Schema::hasColumn('user_orders', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

