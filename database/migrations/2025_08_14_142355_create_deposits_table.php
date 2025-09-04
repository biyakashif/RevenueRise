<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->string('user_id'); // Matches mobile_number
            $table->string('symbol');
            $table->decimal('amount', 15, 2);
            $table->string('address')->default('TBD');
            $table->string('qr_code')->nullable();
            $table->string('slip_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->foreign('user_id')->references('mobile_number')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deposits');
    }
};