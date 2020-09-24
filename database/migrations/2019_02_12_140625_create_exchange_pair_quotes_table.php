<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExchangePairQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_pair_quotes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('market_pair_id');
            $table->integer('exchange_id');
            $table->text('symbol');
            $table->double('price')->nullable();
            $table->double('convert_price')->nullable();
            $table->double('volume_24h')->nullable();
            $table->double('percent_value_24h')->nullable();
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
        Schema::dropIfExists('exchange_pair_quotes');
    }
}
