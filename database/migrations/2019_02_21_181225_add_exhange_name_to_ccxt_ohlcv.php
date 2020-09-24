<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExhangeNameToCcxtOhlcv extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ccxt_ohlcv', function (Blueprint $table) {
            $table->string('exchange_name')->default('binance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ccxt_ohlcv', function (Blueprint $table) {
            $table->removeColumn('exchange_name');
        });
    }
}