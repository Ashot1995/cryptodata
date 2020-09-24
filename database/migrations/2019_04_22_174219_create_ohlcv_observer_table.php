<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOhlcvObserverTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('observer_ohlcv', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('base_id');
            $table->unsignedInteger('quote_id');
            $table->dateTime('time_start');
            $table->dateTime('time_end');
            $table->string('exchange');
            $table->string('interval');
            $table->boolean('fixed')->default(false);
            $table->timestamps();

            $table->unique([
                'base_id',
                'quote_id',
                'time_start',
                'time_end',
                'exchange',
                'interval'
            ], 'uniqueObserve');

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
        Schema::dropIfExists('observer_ohlcv');
    }
}
