<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->text('message')->nullable();
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('sender_type')->default('user'); // 'user', 'admin', 'guest'
            $table->string('guest_session_id')->nullable();
            $table->string('guest_name')->nullable();
            $table->string('guest_mobile')->nullable();
            $table->timestamps();
            
            $table->index(['sender_id', 'recipient_id']);
            $table->index('guest_session_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
};