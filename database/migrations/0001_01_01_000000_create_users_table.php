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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile_number')->unique();
            $table->string('password');
            $table->string('invitation_code')->unique();
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->string('role')->default('user');
            $table->string('vip_level')->default('VIP1'); // Added VIP level with default VIP1
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('mobile_number')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // Align sessions with app design: reference users by mobile_number instead of user_id
            $table->string('mobile_number')->nullable()->index();




        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ensure foreign key checks don't block dropping base tables during full rollbacks
        Schema::disableForeignKeyConstraints();

        // Drop any tables that may reference users to avoid FK errors if present
        if (Schema::hasTable('chat_messages')) {
            Schema::drop('chat_messages');
        }
        if (Schema::hasTable('chats')) {
            Schema::drop('chats');
        }

        // Drop auxiliary tables first
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');

        // Finally drop users
        Schema::dropIfExists('users');

        Schema::enableForeignKeyConstraints();
    }
};