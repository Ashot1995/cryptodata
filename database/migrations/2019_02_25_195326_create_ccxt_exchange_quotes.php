<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCcxtExchangeQuotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ccxt_exchange_quotes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('market_pair_id');
            $table->unsignedInteger('exchange_id');
            $table->timestamp('timestamp')->nullable();
            $table->text('symbol');
            $table->double('price')->nullable();
            $table->double('change_24h')->nullable();
            $table->double('base_volume_24h')->nullable();
            $table->double('quote_volume_24h')->nullable();
            $table->double('percent_value_24h')->nullable();
            $table->timestamps();

            $table->foreign('market_pair_id')->references('id')->on('ccxt_market_pairs')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('exchange_id')->references('id')->on('ccxt_exchanges')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ccxt_exchange_quotes');
    }
}