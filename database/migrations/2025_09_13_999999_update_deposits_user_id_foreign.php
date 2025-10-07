<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('deposits', function (Blueprint $table) {
            // Drop old foreign key
            $table->dropForeign(['user_id']);
            // Change user_id to unsignedBigInteger
            $table->unsignedBigInteger('user_id')->change();
            // Add new foreign key to users.id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            // Change user_id back to string (assuming original was string)
            $table->string('user_id')->change();
            // Add foreign key back to users.mobile_number
            $table->foreign('user_id')->references('mobile_number')->on('users')->onDelete('cascade');
        });
    }
};
