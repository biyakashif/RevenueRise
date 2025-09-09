<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTodaysProfitToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('todays_profit', 10, 2)->default(0.00)->after('balance');
            $table->date('last_profit_reset')->nullable()->after('todays_profit');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['todays_profit', 'last_profit_reset']);
        });
    }
}