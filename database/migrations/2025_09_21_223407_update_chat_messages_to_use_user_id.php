<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop foreign keys first
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropForeign(['recipient_id']);
        });

        // Update sender_id and recipient_id to use user.id instead of mobile_number
        DB::statement('
            UPDATE chat_messages cm
            JOIN users u ON cm.sender_id = u.mobile_number
            SET cm.sender_id = u.id
            WHERE cm.sender_id = u.mobile_number;
        ');

        DB::statement('
            UPDATE chat_messages cm
            JOIN users u ON cm.recipient_id = u.mobile_number
            SET cm.recipient_id = u.id
            WHERE cm.recipient_id = u.mobile_number;
        ');

        // Change column types and foreign keys
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('sender_id')->change();
            $table->unsignedBigInteger('recipient_id')->change();
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropForeign(['recipient_id']);
            $table->string('sender_id')->change();
            $table->string('recipient_id')->change();
            $table->foreign('sender_id')->references('mobile_number')->on('users')->onDelete('cascade');
            $table->foreign('recipient_id')->references('mobile_number')->on('users')->onDelete('cascade');
        });
    }
};
