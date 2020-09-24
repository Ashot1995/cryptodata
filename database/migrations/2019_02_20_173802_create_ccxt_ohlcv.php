<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCcxtOhlcv extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ccxt_ohlcv', function (Blueprint $table) {
            $table->increments('id');
            $table->string('base');
            $table->string('quote');
            $table->decimal('open', 32, 16);
            $table->decimal('high', 32, 16);
            $table->decimal('low', 32, 16);
            $table->decimal('close', 32, 16);
            $table->decimal('volume', 32, 16);
            $table->dateTime('timestamp');
            $table->string('interval', 10);

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
        Schema::dropIfExists('ccxt_ohlcv');
    }
}