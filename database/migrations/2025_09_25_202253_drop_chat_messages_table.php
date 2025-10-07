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

        // Recreate chat_messages table with updated structure
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('sender_type')->default('user'); // 'user' or 'admin' or 'guest'
            $table->text('message')->nullable();
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('guest_session_id')->nullable();
            $table->string('guest_name')->nullable();
            $table->string('guest_mobile')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['sender_id', 'recipient_id']);
            $table->index('guest_session_id');
        });

        // Recreate guest_chat_messages table
        Schema::create('guest_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->string('guest_name')->nullable();
            $table->string('guest_mobile')->nullable();
            $table->text('message')->nullable();
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable();
            $table->boolean('is_from_guest')->default(true);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('session_id');
        });

        // Recreate guest_chats table
        Schema::create('guest_chats', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('guest_name')->nullable();
            $table->string('guest_mobile')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('guest_chat_messages');
        Schema::dropIfExists('guest_chats');
    }
};
