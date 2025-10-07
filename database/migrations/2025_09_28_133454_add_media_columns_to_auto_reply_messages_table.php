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
        Schema::table('auto_reply_messages', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('include_contact_info');
            $table->string('video_path')->nullable()->after('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_reply_messages', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'video_path']);
        });
    }
};
