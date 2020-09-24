<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOhlcvQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ohlcv_quotes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cryptocurrency_id')->unsigned();
            $table->string('convert');
            $table->decimal('open', 32, 16);
            $table->decimal('high', 32, 16);
            $table->decimal('low', 32, 16);
            $table->decimal('close', 32, 16);
            $table->decimal('volume', 32, 16);
            $table->dateTime('timestamp'); //dateTimeTz
            $table->string('time_open'); //dateTimeTz
            $table->string('time_close'); //dateTimeTz
            $table->string('interval', 10);
            $table->string('time_period', 10);

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
        Schema::dropIfExists('ohlcv_quotes');
    }
}
