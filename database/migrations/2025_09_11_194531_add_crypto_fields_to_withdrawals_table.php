<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCryptoFieldsToWithdrawalsTable extends Migration
{
    public function up()
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->unsignedBigInteger('crypto_id')->nullable()->after('user_id');
            $table->string('crypto_symbol', 10)->nullable()->after('crypto_id');
            $table->decimal('crypto_amount', 16, 8)->nullable()->after('crypto_symbol');
            
            $table->foreign('crypto_id')->references('id')->on('crypto_deposit_details')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropForeign(['crypto_id']);
            $table->dropColumn(['crypto_id', 'crypto_symbol', 'crypto_amount']);
        });
    }
}