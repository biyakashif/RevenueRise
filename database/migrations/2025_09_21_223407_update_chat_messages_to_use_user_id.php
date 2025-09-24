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

        // Change column types and foreign keys (data is already user IDs from controllers/Vue updates)
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
        // Drop foreign keys
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        try {
            DB::statement('ALTER TABLE chat_messages DROP FOREIGN KEY chat_messages_sender_id_foreign;');
        } catch (\Exception $e) {
            // Foreign key might not exist
        }
        try {
            DB::statement('ALTER TABLE chat_messages DROP FOREIGN KEY chat_messages_recipient_id_foreign;');
        } catch (\Exception $e) {
            // Foreign key might not exist
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Revert sender_id and recipient_id back to mobile_number
        DB::statement('
            UPDATE chat_messages cm
            JOIN users u ON cm.sender_id = u.id
            SET cm.sender_id = u.mobile_number
            WHERE cm.sender_id = u.id;
        ');

        DB::statement('
            UPDATE chat_messages cm
            JOIN users u ON cm.recipient_id = u.id
            SET cm.recipient_id = u.mobile_number
            WHERE cm.recipient_id = u.id;
        ');

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('sender_id')->change();
            $table->string('recipient_id')->change();
            $table->foreign('sender_id')->references('mobile_number')->on('users')->onDelete('cascade');
            $table->foreign('recipient_id')->references('mobile_number')->on('users')->onDelete('cascade');
        });
    }
};
