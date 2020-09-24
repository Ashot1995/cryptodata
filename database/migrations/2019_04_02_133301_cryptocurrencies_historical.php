<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CryptocurrenciesHistorical extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cryptocurrencies_historical', function (Blueprint $table) {
            $table->increments('cryptocurrencies_historical_id');
            $table->integer('cryptocurrency_id');
            $table->decimal('circulating_supply',36, 16)->nullable();
            $table->decimal('max_supply',36, 16)->nullable();
            $table->decimal('total_supply',36, 16)->nullable();
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
        Schema::dropIfExists('cryptocurrencies_historical');

    }
}
