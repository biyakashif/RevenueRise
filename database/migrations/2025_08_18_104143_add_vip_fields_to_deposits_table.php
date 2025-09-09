<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (!Schema::hasColumn('deposits', 'title')) {
                $table->string('title')->nullable()->after('address');
            }
            if (!Schema::hasColumn('deposits', 'vip_level')) {
                $table->string('vip_level')->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (Schema::hasColumn('deposits', 'vip_level')) {
                $table->dropColumn('vip_level');
            }
            if (Schema::hasColumn('deposits', 'title')) {
                $table->dropColumn('title');
            }
        });
    }
};