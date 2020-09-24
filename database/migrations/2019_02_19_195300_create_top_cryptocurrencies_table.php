<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTopCryptocurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('top_cryptocurrencies', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cryptocurrency_coin_id')->unsigned();
            $table->integer('cryptocurrency_id')->unsigned();
            $table->timestamps();
        });

        Schema::table('top_cryptocurrencies', function($table) {
            $table->foreign('cryptocurrency_id')->references('cryptocurrency_id')->on('cryptocurrencies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('top_cryptocurrencies');
    }
}
