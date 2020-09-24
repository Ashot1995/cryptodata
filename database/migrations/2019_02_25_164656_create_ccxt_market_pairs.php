<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCcxtMarketPairs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ccxt_market_pairs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('base_coin_id');
            $table->unsignedInteger('quote_coin_id');

            $table->foreign('base_coin_id')->references('id')->on('ccxt_cryptocurrencies')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('quote_coin_id')->references('id')->on('ccxt_cryptocurrencies')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ccxt_market_pairs');
    }
}