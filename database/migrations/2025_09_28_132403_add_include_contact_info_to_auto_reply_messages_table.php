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
            $table->boolean('include_contact_info')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_reply_messages', function (Blueprint $table) {
            $table->dropColumn('include_contact_info');
        });
    }
};
