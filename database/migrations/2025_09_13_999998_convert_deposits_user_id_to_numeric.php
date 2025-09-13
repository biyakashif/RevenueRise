<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // Step 1: Update deposits.user_id to numeric id using users.mobile_number
        DB::statement('
            UPDATE deposits d
            JOIN users u ON d.user_id = u.mobile_number
            SET d.user_id = u.id
            WHERE d.user_id = u.mobile_number;
        ');

        // Step 2: Remove orphaned deposits
        DB::statement('
            DELETE FROM deposits WHERE user_id NOT IN (SELECT id FROM users);
        ');

        // Step 3: Change column type and foreign key
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->string('user_id')->change();
            $table->foreign('user_id')->references('mobile_number')->on('users')->onDelete('cascade');
        });
    }
};
