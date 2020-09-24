<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOhlcvPairsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ohlcv_pairs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('base_id');
            $table->unsignedInteger('quote_id');
            $table->string('exchange');
            $table->boolean('30d');
            $table->boolean('1w');
            $table->boolean('1d');
            $table->boolean('12h');
            $table->boolean('4h');
            $table->boolean('1h');
            $table->boolean('30m')->nullable();
            $table->boolean('15m')->nullable();
            $table->boolean('5m')->nullable();
            $table->boolean('1m')->nullable();
            $table->timestamps();

            
            $table->foreign('base_id')->references('cryptocurrency_id')->on('cryptocurrencies');
            $table->foreign('quote_id')->references('cryptocurrency_id')->on('cryptocurrencies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ohlcv_pairs');
    }
}
