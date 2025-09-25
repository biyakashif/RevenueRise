<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('guest_chat_messages');
        Schema::dropIfExists('guest_chats');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('recipient_id');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
};
