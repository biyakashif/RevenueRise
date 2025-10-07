<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('crypto_deposit_details', function (Blueprint $table) {
            $table->dropUnique(['symbol']);
            $table->boolean('is_active')->default(true)->after('address');
        });
    }

    public function down()
    {
        Schema::table('crypto_deposit_details', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->unique('symbol');
        });
    }
};