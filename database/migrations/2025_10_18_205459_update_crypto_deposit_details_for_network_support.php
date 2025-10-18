<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        try {
            DB::statement('ALTER TABLE crypto_deposit_details DROP INDEX crypto_deposit_details_symbol_unique');
        } catch (\Exception $e) {
            // Index doesn't exist, continue
        }
        
        try {
            DB::statement('ALTER TABLE crypto_deposit_details ADD UNIQUE KEY crypto_deposit_details_currency_network_unique (currency, network)');
        } catch (\Exception $e) {
            // Index already exists, continue
        }
    }

    public function down()
    {
        try {
            DB::statement('ALTER TABLE crypto_deposit_details DROP INDEX crypto_deposit_details_currency_network_unique');
        } catch (\Exception $e) {
            // Index doesn't exist
        }
        
        try {
            DB::statement('ALTER TABLE crypto_deposit_details ADD UNIQUE KEY crypto_deposit_details_symbol_unique (symbol)');
        } catch (\Exception $e) {
            // Index already exists
        }
    }
};
