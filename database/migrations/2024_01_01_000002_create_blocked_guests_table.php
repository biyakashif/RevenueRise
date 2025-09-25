<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('blocked_guests', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('name');
            $table->string('mobile_number');
            $table->timestamp('blocked_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('blocked_guests');
    }
};