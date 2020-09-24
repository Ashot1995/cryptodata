<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExchangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchanges', function (Blueprint $table) {
            $table->increments('exchange_id');
            $table->integer('id');
            $table->string('logo', '250')->nullable();
            $table->string('name', '60')->nullable();
            $table->text('urls')->nullable();
            $table->tinyInteger('is_active')->nullable();
            $table->dateTime('first_historical_data')->nullable();
            $table->dateTime('last_historical_data')->nullable();
            $table->integer('quote_id')->nullable();
            $table->integer('num_market_pairs')->nullable();
            $table->string('slug', '60')->nullable();
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
        Schema::dropIfExists('exchanges');
    }
}
