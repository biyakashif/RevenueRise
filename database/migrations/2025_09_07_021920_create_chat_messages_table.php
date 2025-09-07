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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('sender_id');
            $table->string('recipient_id');
            $table->foreign('sender_id')->references('mobile_number')->on('users')->onDelete('cascade');
            $table->foreign('recipient_id')->references('mobile_number')->on('users')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable(); // Added for video uploads
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
